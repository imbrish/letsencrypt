# letsencrypt
PHP script for automatic issuing and renewal of Let's Encrypt certificates on shared hostings.

Domains for SSL certificate should be defined in the `config.yml`

    @todo: config.yml

Then certificates can be issued/renewed by running script manually

    letsencrypt/bin/auto

Or by setting up a cron job for it

    @todo cron job

Script will check if certificates should be renewed and issue/reissue them if so.
Then it will install newly issued certificates in all specified domains using CPanel API.

If can also notify you about actions it took via email, if you wish so.

Command line options

    @todo command line options
