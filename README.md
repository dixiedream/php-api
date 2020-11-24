# PHP API boilerplate
A PHP boilerplate for create simple API without the needing of extra bloat

## Overview
- No framework but only needed 
- PHP as an Apache module
- MariaDb/MySql database integration
- Propel ORM for model creation
- Db based session
- Api-Key authentication

## Setup

1. Install Docker
2. Build development environment
```
# Setup your environment stuff in docker-compose.yml file than
docker-compose build
sh scripts/composer.sh install
docker-compose up
```
3. Start coding

## Tools ##

### Composer ###

```
sh scripts/composer.sh COMPOSER_COMMAND
```

### Database

I use Propel ORM, it's fairly simple to use once you setup it (hopefully this template get the job done).

#### How to (starting from scratch)

```
# Define your database structure (MySql or MariaDb) in `schema.xml`

# Create the db import structure
mkdir sql && sh scripts/propel.sh sql:build

# Insert the new structure to the actual database
sh scripts/propel.sh sql:insert

# Build PHP model upon structure
sh scripts/propel.sh build
```

#### How to (after machine change or for new dev)
```
sh scripts/propel.sh sql:insert
sh scripts/propel.sh migrate
```

#### How to (build updates)
```
# Change your schema accordingly than
# Create a migration file for updating production stuff
sh scripts/propel.sh diff

# Build updated models
sh scripts/propel.sh build

# Update db structure
sh scripts/propel.sh migrate

# If something goes wrong after migration or you unsatistied with it
sh scripts/propel.sh migration:down # As many time as you want
```

[Official Propel website](http://propelorm.org)

## TODO
- Write production stuff readme
- APCU stuff for production environment
- Make possible to use a db connection string for remote connections
