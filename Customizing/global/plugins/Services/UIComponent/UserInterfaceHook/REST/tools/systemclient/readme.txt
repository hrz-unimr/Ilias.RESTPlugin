[ILIAS REST System Client]
--------------------------------------------------------------------------------------------------
The client is based on PHP and can be used in conjunction with the REST API.
Its purpose might be the application in own administration scripts, e.g. for cron jobs.

Requirements:
For Debian a-like systems:
apt-get install php5-cli
apt-get install php5-curl

Installation:
At current only the OAuth2 password credentials type is supported. This means, there must be one
ILIAS (user, passwd) pair and a valid API-Key in order to use the client.
1) Therefore an ILIAS user and a valid API-KEY is needed.
2) Copy (or mv) the file restsystemclient.ini.default to restsystemclient.ini and edit it accordingly.
