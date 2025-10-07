#!/usr/bin/env node

import fetch from "node-fetch";
import { w3cHtmlValidator } from "w3c-html-validator";

const options = {
  continueOnFail: true,
  maxMessageLen: 80,
};

const urls = [
  "http://web:8080/",
  "http://web:8080/fr",
  "http://web:8080/fr/connect",
  "http://web:8080/fr/album/dex",
  "http://web:8080/fr/album/demo",
  "http://web:8080/fr/election/dex",
  "http://web:8080/fr/election/demo",
  "http://web:8080/fr/outerroom",
  "http://web:8080/fr/policy",
  "http://web:8080/fr/legals",
  "http://web:8080/fr/cookies",
  "http://web:8080/fr/trainer",
  "http://web:8080/fr/istration",
  "http://web:8080/fr/istration/action/calculate/pokemon_availabilities",
  "http://web:8080/fr/istration/action/invalidate/reports",
  "http://web:8080/fr/istration/action/update/labels",
];

async function authenticate() {
  const url = "http://web:8080/fr/connect/f/c?t=trainer";
  console.log(`Authenticating with ${url}...`);
  const res = await fetch(url);
  if (!res.ok) throw new Error(`Authentication failed: ${res.status}`);
  console.log("✅ Authenticated successfully.\n");
}

async function validateUrl(website) {
  try {
    console.log(`🔍 Validating ${website}...`);
    const res = await fetch(website);
    const html = await res.text();
    const results = await w3cHtmlValidator.validate({ html: html });
    w3cHtmlValidator.reporter(results, options);
  } catch (err) {
    console.error(`❌ Validation failed for ${website}:`, err.message);
  }
}

(async () => {
  try {
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