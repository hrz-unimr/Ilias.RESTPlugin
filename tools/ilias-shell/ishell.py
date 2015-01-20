import ConfigParser
import json
import urllib
import urllib2

class IShell:
   """	
		ILIAS-Shell
		Enables personalized and administrative operations on the ILIAS LMS.
		v.1.1
   """

   def __init__(self):
		settings = ConfigParser.ConfigParser();
		settings.read('ishell.ini')
		self.api_key = settings.get('Authentification','api_key')
		self.username = settings.get('Authentification','username')
		self.userpass = settings.get('Authentification','password')
		self.oauth2_endpoint = settings.get('Authentification','oauth2_endpoint')
		self.ilias_client_id = settings.get('System','ilias_client_id')
		self.rest_endpoint = settings.get('System','rest_endpoint')
		self.connect()

   def connect(self):
		params = urllib.urlencode({'grant_type': 'password', #Invoke OAuth2 mechanism via "resources owner credentials".
		'username': self.username,
		'password': self.userpass,
		'client_id': self.api_key,
		'ilias_client_id' : self.ilias_client_id
		})
		response = urllib2.urlopen(self.rest_endpoint+self.oauth2_endpoint, params).read()
		data = json.loads(response)
		self.token = data['access_token']
		#f = open('token', 'w')
		#f.write('header="Authorization: Bearer ' + tokenstr + '"')
		#f.close()
		print 'Welcome to the ILIAS-Shell'
		print 'Connected successfully to ' + self.rest_endpoint + self.oauth2_endpoint
		print 'Retrieved OAuth2 Token: ' + self.token
   
   def printIntro(self):
		print '--'
		print 'Hint: to get started just type $il. + <tab> to see some functions.'
		print 'Example: $il.getRoutes(); followed by $il.show()'
   
   def getRoutes(self):
		response = urllib2.urlopen(self.rest_endpoint+'/routes').read()
		data = json.loads(response)
		self.latestResponse = data
		return data

   def show(self):
		 print(json.dumps(self.latestResponse, indent=2))
		 
   def dump(self, jsondata):
		f = open('dump.json','w')
		f.write(json.dumps(jsondata, indent=2))
		f.close
	
   def get(self, routeStr):
		#response = urllib2.urlopen(self.rest_endpoint+'/'+routeStr).read()
		request = urllib2.Request(self.rest_endpoint+'/'+routeStr)
		request.add_header('Authorization', ' Bearer '+ self.token)
		response = urllib2.urlopen(request).read()
		#response
		data = json.loads(response)
		self.latestResponse = data
		return data

   def users(self):
		self.get('/v1/users');
		self.show();
