#!/usr/bin/env node

import fs from "fs/promises";
import path from "path";
import fetch from "node-fetch";
import { w3cHtmlValidator } from "w3c-html-validator";

const BASE_URI = "http://web:8080/";

const urls = [
  "",
  "fr",
  "fr/connect",
  "fr/album/dex",
  "fr/album/demolite",
  "fr/election/dex",
  "fr/election/demo",
  "fr/outerroom",
  "fr/policy",
  "fr/legals",
  "fr/cookies",
  "en/policy",
  "en/legals",
  "en/cookies",
  "fr/trainer",
  "fr/istration",
  "fr/istration/action/calculate/pokemon_availabilities",
  "fr/istration/action/invalidate/reports",
  "fr/istration/action/update/labels",
];

const OUT_DIR = "var/invalid-html";

async function authenticate() {
  const url = BASE_URI+"fr/connect/f/c?t=trainer";
  console.log(`Authenticating with ${url}...`);
  const res = await fetch(url);
  if (!res.ok) throw new Error(`Authentication failed: ${res.status}`);
  console.log("✅ Authenticated successfully.\n");
}

async function cleanOutputDir() {
  try {
    await fs.rm(OUT_DIR, { recursive: true, force: true });
    await fs.mkdir(OUT_DIR, { recursive: true });
    console.log(`🧹 Cleaned output directory: ${OUT_DIR}\n`);
  } catch (err) {
    console.error(`⚠️ Could not clean ${OUT_DIR}:`, err.message);
  }
}

async function validateUrl(website) {
  try {
    console.log(`🔍 Validating ${website}...`);
    
    const res = await fetch(BASE_URI+website);
    let html = await res.text();

    // Remove Symfony Debug toolbar
    html = html
      .split("\n")
      .filter(line => !line.includes("_wdt/styles"))
      .join("\n");

    const results = await w3cHtmlValidator.validate({ 
      html: html,
    });
    w3cHtmlValidator.reporter(results, {continueOnFail: true});

    const filenameSafe = website
      .replace(/^https?:\/\//, "")
      .replace(/[^\w.-]+/g, "_");
    const outDir = "var/invalid-html";
    const outPath = path.join(outDir, `${filenameSafe}.html`);

    await fs.mkdir(outDir, { recursive: true });
    await fs.writeFile(outPath, html, "utf8");

  } catch (err) {
    console.error(`❌ Validation failed for ${website}:`, err.message);
  }
}

(async () => {
  try {
    await cleanOutputDir();
    await authenticate();

    for (const website of urls) {
      await validateUrl(website);
      console.log(""); // séparation visuelle
    }

    console.log("🏁 Validation completed.");
  } catch (err) {
    console.error("💥 Error:", err.message);
    process.exit(1);
  }
})();