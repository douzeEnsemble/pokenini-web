#!/usr/bin/env python3
"""Bump the dependencies that Dependabot cannot handle, to their latest version.

  php   PHP docker image (Dockerfile), the "php" constraint of every composer.json,
        the lock hashes, setup-php and the docs
  pins  images pinned in Makefile / workflows / Dockerfile and the Moco version

Files are edited in place. A markdown summary is appended to --summary when
something changed. Nothing is adapted beyond the version strings: the CI of
the resulting PR tells whether the bump is safe.
"""

import argparse
import json
import re
import subprocess
import urllib.request
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
COMPOSER_DIRS = [".", *sorted(p.parent.name and f"tools/{p.parent.name}" for p in (ROOT / "tools").glob("*/composer.json"))]

# (image, registry, files)
PINS = [
    ("mstruebing/editorconfig-checker", "hub", ["Makefile", ".github/workflows/ci_codequality.yml"]),
    ("zavoloklom/dclint", "hub", ["Makefile", ".github/workflows/ci_infraquality.yml"]),
    ("dotenvlinter/dotenv-linter", "hub", ["Makefile"]),
    ("hadolint/hadolint", "hub", ["Makefile"]),
    ("ghcr.io/symfony-cli/symfony-cli", "ghcr", [".docker/php/Dockerfile"]),
]
MOCO_METADATA = "https://repo1.maven.org/maven2/com/github/dreamhead/moco-runner/maven-metadata.xml"


def fetch(url, headers=None):
    request = urllib.request.Request(url, headers={"User-Agent": "infra-updates", **(headers or {})})
    with urllib.request.urlopen(request, timeout=30) as response:
        return response.read().decode(), response.headers


def version_key(value):
    return tuple(int(n) for n in re.findall(r"\d+", value))


def hub_tags(repo, name_filter="", pages=5):
    if "/" not in repo:
        repo = f"library/{repo}"
    url = f"https://hub.docker.com/v2/repositories/{repo}/tags?page_size=100&ordering=last_updated&name={name_filter}"
    tags = []
    for _ in range(pages):
        if not url:
            break
        body, _ = fetch(url)
        data = json.loads(body)
        tags += [r["name"] for r in data["results"]]
        url = data.get("next")
    return tags


def ghcr_tags(repo):
    token = json.loads(fetch(f"https://ghcr.io/token?scope=repository:{repo}:pull&service=ghcr.io")[0])["token"]
    url = f"https://ghcr.io/v2/{repo}/tags/list?n=1000"
    tags = []
    while url:
        body, headers = fetch(url, {"Authorization": f"Bearer {token}"})
        tags += json.loads(body)["tags"]
        link = headers.get("Link", "")
        match = re.search(r"<([^>]+)>;\s*rel=\"next\"", link)
        url = f"https://ghcr.io{match.group(1)}" if match else None
    return tags


def read(path):
    return (ROOT / path).read_text()


def write(path, content):
    (ROOT / path).write_text(content)


def summarize(path, line):
    print(line)
    if path:
        with open(path, "a") as handle:
            handle.write(line + "\n")


def bump_php(summary):
    dockerfile = ".docker/php/Dockerfile"
    current = re.search(r"^FROM php:(\d+\.\d+\.\d+)-fpm-alpine(\d+\.\d+) ", read(dockerfile), re.M)
    current_php, current_alpine = current.group(1), current.group(2)

    candidates = []
    for tag in hub_tags("php", "fpm-alpine"):
        match = re.fullmatch(r"(\d+\.\d+\.\d+)-fpm-alpine(\d+\.\d+)", tag)
        if match:
            candidates.append((version_key(match.group(1)), version_key(match.group(2)), match.group(1), match.group(2)))
    if not candidates:
        return
    _, _, new_php, new_alpine = max(candidates)
    if (version_key(new_php), version_key(new_alpine)) <= (version_key(current_php), version_key(current_alpine)):
        print(f"php {current_php}-alpine{current_alpine} is up to date")
        return

    write(dockerfile, read(dockerfile).replace(
        f"php:{current_php}-fpm-alpine{current_alpine}", f"php:{new_php}-fpm-alpine{new_alpine}", 1))

    for directory in COMPOSER_DIRS:
        path = f"{directory}/composer.json".removeprefix("./")
        write(path, re.sub(r'("php": ">=)[\d.]+(")', rf"\g<1>{new_php}\2", read(path)))
        subprocess.run(
            ["composer", "update", "--lock", "--ignore-platform-req=php", "--no-scripts", "--no-install", "--working-dir", directory],
            cwd=ROOT, check=True)

    minor = ".".join(new_php.split(".")[:2])
    action = ".github/actions/local-php/action.yml"
    write(action, re.sub(r'(php-version: ")[\d.]+(")', rf"\g<1>{minor}\2", read(action)))

    docs = "doc/update.md"
    write(docs, "\n".join(
        line.replace(current_php, new_php).replace(f"alpine{current_alpine}", f"alpine{new_alpine}")
        if line.startswith("| `php` |") else line
        for line in read(docs).split("\n")))

    summarize(summary, f"- PHP image: `{current_php}-alpine{current_alpine}` → `{new_php}-alpine{new_alpine}`")


def bump_pins(summary):
    for image, registry, files in PINS:
        repo = image.removeprefix("ghcr.io/")
        tags = None
        for file in files:
            content = read(file)
            for match in set(re.findall(re.escape(image) + r":(v?\d[\w.]*(?:-[\w.]+)?)", content)):
                shape = re.sub(r"\d+", r"\\d+", re.escape(match).replace(r"\.", "."))
                shape = shape.replace(".", r"\.")
                if tags is None:
                    tags = ghcr_tags(repo) if registry == "ghcr" else hub_tags(repo)
                newer = [t for t in tags if re.fullmatch(shape, t)]
                best = max(newer, key=version_key, default=match)
                if version_key(best) > version_key(match):
                    write(file, content.replace(f"{image}:{match}", f"{image}:{best}"))
                    content = read(file)
                    summarize(summary, f"- `{image}`: `{match}` → `{best}` ({file})")

    file = "docker-compose.yaml"
    content = read(file)
    current = re.search(r"MOCO_VERSION:-(\d+\.\d+\.\d+)\}", content).group(1)
    versions = re.findall(r"<version>(\d+\.\d+\.\d+)</version>", fetch(MOCO_METADATA)[0])
    best = max(versions, key=version_key)
    if version_key(best) > version_key(current):
        write(file, content.replace(f"MOCO_VERSION:-{current}}}", f"MOCO_VERSION:-{best}}}"))
        summarize(summary, f"- Moco: `{current}` → `{best}` ({file})")


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("target", choices=["php", "pins"])
    parser.add_argument("--summary")
    args = parser.parse_args()
    {"php": bump_php, "pins": bump_pins}[args.target](args.summary)


if __name__ == "__main__":
    main()
