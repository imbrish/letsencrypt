# letsencrypt
PHP script for automatic issuing and renewal of [Let's Encrypt](https://letsencrypt.org/) SSL certificates on shared hostings.

## Credits

This script is in fact a wrapper for two other:

- https://github.com/kelunik/acme-client
- https://github.com/neurobin/sslic

Especially `acme-client` and [this article](https://neurobin.org/docs/web/fully-automated-letsencrypt-integration-with-cpanel/) greatly inspired development. Thanks to the authors!

## Requirements

- Access to CPanel
- Access to server via SSH
- PHP 5.4 or higher

## Installation

We will use [composer](https://getcomposer.org/) to easily install all dependencies.
First we connect to our server with SSH and then:

```bash
# Clone the repository
git clone https://github.com/imbrish/letsencrypt

# Navigate to repository folder
cd letsencrypt

# Install dependencies
composer install

# Create the config file, should be customized afterwards
cp config.yml.example config.yml

# Allow only owner to access the config
chmod 600 config.yml
```

## Configuration

Domains for certificate should be defined in the `config.yml`:

```yml
# Server to use, "letsencrypt" and "letsencrypt:staging" are valid shortcuts.
# The latter can help when testing as it offers more lenient usage quotas.
server: letsencrypt

# Custom nameserver IP used by the "acme issue" command.
# For example Google public DNS "8.8.8.8" or "8.8.4.4", or Cloudflare 1.1.1.1.
nameserver: false

# Base directory for certificate document roots.
home: /home/user

# List of separate certificates to issue and install.
certificates:
    # For each certificate, there are a few options:
    # bits:    Number of bits for the domain private key, from 2048 to 4096.
    # domains: Map of document roots to domains. Maps each path to one or multiple
    #          domains. If one domain is given, it's automatically converted to an
    #          array. The first domain will be the common name.
    - bits: 4096
      domains:
        /public_html:
            - example.com
            - www.example.com
        /sub/public_html:
            - sub.example.com
            - www.sub.example.com
    - bits: 2048
      domains:
        /another/public_html:
            - another.com
            - www.another.com

# Renew a certificate if it is due to expire within so many days.
renew: 30

# E-mail to use for the Let's Encrypt registration. This e-mail will receive
# certificate expiration notices from Let's Encrypt.
email: me@example.com

# E-mail to notify about errors or certificates issued during the execution.
# Used only when command is called with a "-notify" or "-n" flag.
notify: me@example.com

# CPanel credentials necessary to install the certificates.
# Do not share your configuration file after filling this!
cpanel:
    user: example
    password: secret

# By default certificates will be installed in CPanel for all domains listed above.
# Domains can be filtered by a whitelist of names to accept and/or blacklist to reject.
# The www prefix should be omitted because it is trimmed before the installation.
install:
    whitelist:
    blacklist:
        - sub.example.com
```

## Usage

Certificates can be issued/renewed by running script manually.

Run script as executable:

```bash
# Make the script executable
chmod 775 bin/letsencrypt

# Run it
bin/letsencrypt
```

Alternatively use `php`:

```bash
php bin/letsencrypt
```

Script will check if certificates should be renewed and issue/reissue them if so.
Then it will install newly issued certificates in all specified domains using CPanel API.

It can also notify you about actions it took via email, if you wish so.

Command line options:

`-n`, `--notify` - Send email notification about errors or issued certificates

`-c`, `--config` - Name of the configuration file including extension, by default `config.yml`

`-h`, `--help` - Display the help message

Command line arguments:

Optional list of certificate common names to issue and install only a subset of certificates defined in the configuration file.

For example to use configuration file `example.yml`, issue and install only certificate for `example.com` and send email notification to address defined in the config:

```bash
php bin/letsencrypt -c custom.yml -n -- example.com
```

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

## Todo

- Make script standalone

    + Use https://github.com/mgufrone/cpanel-php to communicate with CPanel API directly
    + Use https://github.com/kelunik/acme to issue certificates

- Improve output, errors and emails
- Refactor command logic into a separate class
- Switch to https://github.com/symfony/console or similar due to issues with https://github.com/thephpleague/climate
