# Diagramme UML de Classes
## Projet : Gestion des Notes et Absences des Étudiants

---

## 📋 Description

Ce dossier contient le diagramme UML de classes professionnel pour l'application web de gestion des notes et des absences des étudiants.

---

## 📁 Contenu du Dossier

| Fichier | Description |
|--------|-------------|
| `diagramme_uml_classes.png` | Export PNG haute qualité du diagramme UML |
| `diagramme_uml_classes.drawio` | Fichier source modifiable Draw.io |
| `README.md` | Documentation du diagramme |

---

## 🏗️ Classes Identifiées

### 1. Utilisateur *(Classe mère abstraite)*
- **Attributs** : idUtilisateur, nom, prenom, email, motDePasse, role
- **Méthodes** : seConnecter(), seDeconnecter()

### 2. Étudiant *(hérite de Utilisateur)*
- **Attributs** : matricule, niveau, dateInscription
- **Méthodes** : consulterNotes(), consulterAbsences()

### 3. Enseignant *(hérite de Utilisateur)*
- **Attributs** : specialite, grade
- **Méthodes** : ajouterNote(), modifierNote(), enregistrerAbsence()

### 4. Matière
- **Attributs** : idMatiere, nomMatiere, coefficient
- **Méthodes** : creerMatiere(), modifierMatiere()

### 5. Note
- **Attributs** : idNote, valeur, dateEvaluation, typeEvaluation
- **Méthodes** : enregistrer(), modifier(), supprimer()

### 6. Absence
- **Attributs** : idAbsence, dateAbsence, motif, statut
- **Méthodes** : enregistrer(), justifier()

---

## 🔗 Relations UML

### Héritage
```
Utilisateur
├── Étudiant
└── Enseignant
```

### Associations
| Relation | Multiplicité |
|---------|-------------|
| Étudiant → Note | 1 à 0..* |
| Matière → Note | 1 à 0..* |
| Étudiant → Absence | 1 à 0..* |
| Enseignant → Matière | 1 à 0..* |

---

## 🛠️ Comment Ouvrir le Fichier Draw.io

1. Aller sur [draw.io](https://app.diagrams.net/)
2. Fichier → Ouvrir → Choisir `diagramme_uml_classes.drawio`
3. Le diagramme s'ouvre en mode édition complète

---

## ✅ Checklist de Réalisation

- [x] Identifier les classes (6 classes identifiées)
- [x] Définir les attributs (avec types de données)
- [x] Définir les méthodes (avec types de retour)
- [x] Définir les relations (héritage + associations avec multiplicités)
- [x] Réaliser le diagramme Draw.io (fichier .drawio inclus)
- [ ] Validation par le responsable
- [x] Export PNG haute qualité
- [x] Dépôt GitHub (dossier UML)

---

*Réalisé par : Étudiant 6 – Conception du Diagramme UML de Classes*
*Date : Juin 2026*
