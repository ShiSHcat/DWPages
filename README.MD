# DWPages dump.
[![author shishcat](https://img.shields.io/badge/author-ShiSHcat8214-red)](https://shishc.at)
![license AGPL](https://img.shields.io/badge/LICENSE-AGPL-green)
[![MadelineProto](https://img.shields.io/badge/framework--telegram-MadelineProto-yellow)](https://github.com/danog/MadelineProto)
[![AMP](https://img.shields.io/badge/framework--php-AMP-yellow)](https://github.com/amphp/)

Dump of the DWPages sources.\
I made this project to get experience with the [Amp Framework](https://github.com/amphp).\
The idea of this project is to make a static hosting that gets pages and assets from Telegram.\
The code is very messy and full of bugs, I'd advice a rewrite.

### ⚠️ I don't assume any responsability. The software in this repository is provided without any warranty. If you decide to run this, you fully understand what it does and everything caused by it is completely your fault.

## Requirements
A cloudflared domain and access to the Cloudflare API\
MySQL\
A DWGram Instance\
systemd (raccomended, you can install it yourself on whatever you want but I give configs only for systemd)

## Installation 
⚠️ I don't assume any responsability. The software in this repository is provided without any warranty. If you decide to run this, you fully understand what it does and everything caused by it is completely your fault.

1. Make the DB, guide down the readme
2. Search `SECRET` and change the harddcoded stuff
3. Run index! (You might want to install it with systemd to survive reboots)

## DB guide
Make a database called dwpages and a table called access_data as following:
![guide](https://support.iranianvacuums.com/attachment/936f666f80d3b2da3df64ecd34efb3ff.png)