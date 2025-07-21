#!/bin/bash

# Fichier contenant la liste des fichiers à vérifier
fichier_liste="liste_fichiers.txt"

# Lire chaque ligne du fichier
while IFS= read -r fichier
do
  # Vérifier si le fichier existe
  if [ -f "$fichier" ]; then
    echo "$fichier existe"
  else
    echo "$fichier existe pas"
  fi
done < "$fichier_liste"
