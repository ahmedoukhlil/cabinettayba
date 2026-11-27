# Multitenancy par base de données

L'application utilise une base centrale (`landlord`) ne contenant que le registre des cabinets.
Chaque cabinet conserve ensuite sa propre base métier complète et indépendante.

## Résolution d'un cabinet

Le middleware résout le cabinet dans cet ordre :

1. correspondance exacte du domaine enregistré ;
2. sous-domaine du domaine central (`cabinet.example.com`) ;
3. cabinet par défaut configuré, utile pour une IP ou une installation de transition.

La connexion métier est sélectionnée avant le démarrage de la session et avant
l'authentification. Les caches fichiers et les disques Laravel sont également séparés
par le slug du cabinet.

## Activation initiale

Créer d'abord la base centrale, puis renseigner les variables `LANDLORD_DB_*`.

```bash
php artisan migrate --database=landlord --path=database/migrations/landlord --force
```

Enregistrer la base existante comme premier cabinet :

```bash
php artisan tenant:add cabinet-principal cabinet_principal \
  --name="Cabinet principal" \
  --domain="cabinet.example.com"
```

Activer ensuite la résolution dans `.env` :

```dotenv
TENANCY_ENABLED=true
TENANCY_CENTRAL_DOMAINS=example.com
TENANCY_DEFAULT_TENANT=
```

Puis vider la configuration :

```bash
php artisan optimize:clear
php artisan tenant:list
```

Laisser `TENANCY_DEFAULT_TENANT` vide en production évite qu'un domaine inconnu
soit dirigé vers la base d'un cabinet existant.

## Administration centrale

Le domaine exact déclaré dans `TENANCY_CENTRAL_DOMAINS` ne charge aucune base
métier. Il expose l'administration centrale sous `/admin`, par exemple
`https://rdvsysdentalmr.com/admin`.

Créer la table et le premier administrateur :

```bash
php artisan migrate --database=landlord --path=database/migrations/landlord --force
php artisan central-admin:create admin@example.com --name="Administrateur"
```

Le mot de passe est demandé de manière interactive et n'apparaît ni dans la
commande ni dans l'historique du terminal. L'interface permet de créer, modifier,
suspendre et réactiver les cabinets. Chaque création ou modification teste la
connexion SQL et vérifie les tables métier requises avant la sauvegarde.

Les sessions doivent rester propres à chaque hôte :

```dotenv
SESSION_DOMAIN=null
SESSION_SECURE_COOKIE=true
```

Ne pas définir `SESSION_DOMAIN=.example.com`, car cela partagerait un cookie de
session entre le domaine central et tous les cabinets.

## Ajouter un nouveau cabinet

Les migrations historiques actuelles ne permettent pas encore de construire une base
vide de manière fiable : certaines migrations d'altération précèdent la création des
tables correspondantes. Pour cette première version, créer les nouveaux cabinets à
partir d'une copie assainie de la structure d'une base de référence, sans données
patients ni utilisateurs.

Une fois cette base créée :

```bash
php artisan tenant:add clinique-nord cabinet_clinique_nord \
  --name="Clinique Nord" \
  --domain="clinique-nord.example.com"
```

La commande vérifie la connexion et la présence des tables `t_user`, `patients`,
`rendezvous` et `facture` avant d'enregistrer le cabinet.

## Configurer l'identité du cabinet

Le nom, le logo, les couleurs et les coordonnées sont stockés dans la base centrale
`landlord`. La base métier du cabinet n'est pas modifiée.

```bash
php artisan migrate --database=landlord --path=database/migrations/landlord --force
php artisan storage:link
php artisan tenant:brand clinique-nord \
  --name="Clinique Nord" \
  --name-ar="عيادة الشمال" \
  --logo=/chemin/vers/logo.png \
  --primary="#1e3a8a" \
  --secondary="#0e7490" \
  --prefix="NORD" \
  --phone="+222 00 00 00 00" \
  --email="contact@example.com" \
  --address="Adresse du cabinet"
```

La commande peut être relancée avec uniquement les options à modifier. En l'absence
de valeur centrale, l'application reprend temporairement les informations historiques
de `infocabinet`, puis utilise une identité neutre en dernier recours.

## Règles d'exploitation

- Le driver de session doit rester `file`, `redis` ou une connexion centrale, jamais
  la connexion dynamique `tenant`.
- Une tâche de queue doit transporter le slug ou l'identifiant du cabinet et activer
  sa connexion avant toute requête métier.
- Une sauvegarde et une restauration sont réalisées base par base.
- Les identifiants SQL spécifiques sont chiffrés par Laravel avec `APP_KEY`.
- `TENANCY_ENABLED=false` conserve le fonctionnement historique sur `DB_CONNECTION`.
