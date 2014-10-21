#!/usr/bin/env python
# This (python) shell script requests an OAuth2 token and saves it 
# as a curl config file. 
#
# 2014-08-11 by D.Schaefer

import json
import urllib
import urllib2

url = 'http://localhost/restplugin.php/v1/oauth2/token'
params = urllib.urlencode({
  'grant_type': 'password', #Invoke OAuth2 mechanism via "resources owner credentials".
  'username': 'root',
  'password': 'homer'
})
response = urllib2.urlopen(url, params).read()

data = json.loads(response)
tokenstr = data['access_token']
f = open('token', 'w')
f.write('header="Authorization: Bearer ' + tokenstr + '"')
f.close()

print 'Done writing curl config file with name "token".'
print 'You can now use the command curl -K token -X GET ... as usual.'

