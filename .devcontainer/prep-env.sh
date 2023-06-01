#!/bin/bash

# install php dependencies
composer install

# turn off xdebug messages
echo "xdebug.remote_enable = 0" | sudo tee -a /usr/local/etc/php/conf.d/xdebug.ini > /dev/null
