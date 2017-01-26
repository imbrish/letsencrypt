# letsencrypt
PHP script for automatic issuing and renewal of Let's Encrypt SSL certificates on shared hostings.

## Installation

We use composer, so we can easily install all dependencies:

```bash
# Clone repository
git clone https://github.com/imbrish/letsencrypt

# Navigate to repository folder
cd letsencrypt

# Install dependencies
composer install

# Set up config file
cp config.yml.example config.yml
```

## Configuration

Domains for certificate should be defined in the `config.yml`:

```yml
# Base directory for domain paths
home: /home/user

# Server to use. URL to the ACME directory.
# "letsencrypt" and "letsencrypt:staging" are valid shortcuts.
server: letsencrypt

# E-mail to use for the setup.
email: me@example.com

# Renew certificate if it expires within so many days.
renew: 30

# List of certificates to issue.
certificates:
    # For each certificate, there are a few options.
    #
    # bits:  Number of bits for the domain private key
    # paths: Map of document roots to domains. Maps each path to one or multiple
    #        domains. If one domain is given, it's automatically converted to an
    #        array. The first domain will be the common name.
    - bits: 4096
      domains:
        /public_html:
            - example.com
            - www.example.com
        /sub/public_html:
            - sub.example.com
            - www.sub.example.com

# E-mail to send notifications.
notify: me@example.com

# CPanel credentials.
cpanel:
    user: example
    password: secret

# Custom nameserver ip used by 'acme issue' command
# For example google public dns "8.8.8.8" or "8.8.4.4"
nameserver: false
```

## Usage

Certificates can be issued/renewed by running script manually.

Running script as executable:

```bash
# Make the script executable
chmod +x bin/letsencrypt

# Run it
bin/letsencrypt
```

Running script using `php`:

```bash
php bin/letsencrypt
```

Script will check if certificates should be renewed and issue/reissue them if so.
Then it will install newly issued certificates in all specified domains using CPanel API.

It can also notify you about actions it took via email, if you wish so.

Command line options:

`-n`, `-notify` - Send email for errors / issued certificates to notify email defined in config.

## Cron job

Even more automation by setting up a cron job:

```
0 0 * * * /path/to/php-cli /home/user/letsencrypt/bin/letsencrypt -n
```

It will run the script every day at midnight.

You can check path to cli version of php by connecting to your hosting via ssh and running:

```bash
which php
```
