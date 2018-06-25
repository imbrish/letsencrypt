# letsencrypt
PHP script to automatically issue and renew [Let's Encrypt](https://letsencrypt.org/) SSL certificates on shared hostings.

## Credits

Development of the script was inspired by [this article](https://neurobin.org/docs/web/fully-automated-letsencrypt-integration-with-cpanel/).

Checking, issuing and renewal of certificates is handled using [`kelunik/acme-client`](https://github.com/kelunik/acme-client).

Thanks to the authors!

## Requirements

- PHP 5.4 or higher
- Access to server via SSH
- Access to cPanel via UAPI

## Installation

We will use [composer](https://getcomposer.org/) to easily install dependencies.

First connect to the server with SSH and then:

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

## Updating

To update the script to the newest version:

```bash
# Pull changes from the repository
git fetch
git reset --hard origin/master

# Install dependencies
composer install

# Optionally restore executable mode
chmod 775 bin/letsencrypt
```

Remember to review your configuration against `config.yml.example` for possible changes!

## Configuration

All configuration should be placed in the `config.yml`:

```yml
# Server to use, "letsencrypt" and "letsencrypt:staging" are valid shortcuts.
# The latter can help when testing as it offers more lenient usage quotas.
server: letsencrypt

# Custom nameserver IP used by the "acme issue" command.
# For example Google public DNS "8.8.8.8" or "8.8.4.4", or Cloudflare 1.1.1.1.
nameserver: null

# Base directory of the certificate document roots.
home: /home/user

# List of certificates to issue and install, for each there are a few options:
# bits:    Number of bits for the domain private key, from 2048 to 4096.
# domains: Map of document roots to domains. Maps paths of challenge directories
#          to the domains for which certificate should be issued. The very first
#          domain will be the common name for the certificate and its directory.
certificates:
    # This is the first certificate, common name and directory will be example.com.
    # It will be issued for domains example.com and sub.example.com with www variants.
    # The challenge files go to /home/user/public_html and /home/user/sub/public_html.
    - bits: 4096
      domains:
        /public_html:
            - example.com
            - www.example.com
        /sub/public_html:
            - sub.example.com
            - www.sub.example.com
    # This is the second certificate, common name and directory will be another.com.
    # It will be issued for domain another.com with www variant. The challenge files
    # go to /home/user/another/public_html.
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

# The cPanel user for which certificates should be installed.
# Necessary only when logged-in as a root.
user: user

# By default certificates will be installed in cPanel for all domains listed above.
# Domains can be filtered by a whitelist of names to accept and/or blacklist to reject.
# The www prefix should be omitted because it is trimmed before the installation.
install:
    whitelist:
    blacklist:
        - sub.example.com
```

## Usage

Certificates can be issued/renewed by running the command manually.

Run script as an executable:

```bash
# Make the script executable
chmod 775 bin/letsencrypt

# Run it
bin/letsencrypt
```

Alternatively use `php` to execute the script:

```bash
php bin/letsencrypt
```

Script will check if certificates should be renewed and issue/reissue them if so.
Then it will install newly issued certificates in all specified domains using cPanel API.

It can also notify you about actions it took via email, if you wish so.

Command line arguments:

| Option | Description |
| --- | --- |
| `-n`, `--notify` | Send email notification about errors or issued certificates |
| `-c`, `--config` | Name of the configuration file including extension, by default `config.yml` |
| `-v`, `--verbose` | Enable verbose output |
| `-h`, `--help` | Display the help message |
| | Optional list of certificate common names to issue and install only <br> a subset of certificates defined in the configuration file. |

For example to use configuration file `example.yml`, issue and install only certificate for `example.com` and send email notification to the address defined in the config:

```bash
php bin/letsencrypt -c custom.yml -n -- example.com
```

## Automation

Issuing, renewal and installation of certificates can be automated by setting up a cron job:

```
0 0 * * * /path/to/php-cli /home/user/letsencrypt/bin/letsencrypt -n
```

It will run the script every day at midnight and notify you about errors or issued certificates to an email defined in the configuration file.

You can check path to CLI version of PHP by connecting to your hosting via SSH and running:

```bash
which php
```

## Alternatives

The [`Neilpang/acme.sh`](https://github.com/Neilpang/acme.sh) may be a more robust alternative. A few relevant guides:

- https://github.com/Neilpang/acme.sh/wiki/How-to-install
- https://github.com/Neilpang/acme.sh/wiki/How-to-issue-a-cert
- https://github.com/Neilpang/acme.sh/wiki/Simple-guide-to-add-TLS-cert-to-cpanel
