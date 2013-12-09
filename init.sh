#!/bin/bash

cd /home/ec2-user/user-repo
curl -sS https://getcomposer.org/installer | php
php ./composer.phar install
