import ConfigParser
import json
import urllib
import urllib2
import shutil

class IShell:
    """  
    ILIAS-Shell
    For personalized and administrative operations on your ILIAS LMS.
    v.1.4
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

    def getToken(self):
        #print '[Debug] In get Token'
	#print self.username
	#print self.userpass
	#print self.api_key
	#print self.ilias_client_id
	#print self.rest_endpoint
	#print self.oauth2_endpoint
	params = urllib.urlencode({'grant_type': 'password', 	#Invoke OAuth2 mechanism via "resources owner credentials".
        'api_key': self.api_key,
        'username': self.username,
        'password': self.userpass,
        'client_id': self.api_key,
        'ilias_client_id' : self.ilias_client_id
        })
        response = urllib2.urlopen(self.rest_endpoint+self.oauth2_endpoint, params).read()
        #print response
	data = json.loads(response)
        self.token = data['access_token']


    def connect(self):
        self.getToken()
#f = open('token', 'w')
#f.write('header="Authorization: Bearer ' + tokenstr + '"')
#f.close()
        print 'Welcome to the ILIAS-Shell'
	print '> Connected with host: ' + self.rest_endpoint
	print '> Retrieved OAuth2 token: ' + self.token[1:15] + '...'
	print '> Using api-key: ' + self.api_key   

    def printIntro(self):
        print '--'
        print 'Hint: to get started just type i.<tab> to see some functions.'
        print 'Example: i.getRoutes(), see variable i.response afterwards'
   
    def getRoutes(self):
        response = urllib2.urlopen(self.rest_endpoint+'/routes').read()
        data = json.loads(response)
        self.response = data
        self.show()

    def show(self):
        print(json.dumps(self.response, indent=2))
     
    def dump(self, jsondata):
        f = open('dump.json','w')
        f.write(json.dumps(jsondata, indent=2))
        f.close
  
    def post(self, routeStr, data):
        if routeStr[0]=='/':
                routeStr = routeStr[1:]
	try:
		request = urllib2.Request(self.rest_endpoint+'/'+routeStr)
        	request.add_header('Authorization', ' Bearer '+ self.token)
        	enc_data = urllib.urlencode(data)
        	response = urllib2.urlopen(url=request, data=enc_data).read()
        	data = json.loads(response)
        	self.response = data
		self.show()
	except urllib2.HTTPError as e:
		print '> ' + str(e.code) + ' - ' + e.reason
		print e.read()

    def get(self, routeStr):
	if routeStr[0]=='/':
		routeStr = routeStr[1:]
        try:
		request = urllib2.Request(self.rest_endpoint+'/'+routeStr)
        	request.add_header('Authorization', ' Bearer '+ self.token)
        	response = urllib2.urlopen(request).read()
        	data = json.loads(response)
        	self.response = data
		self.show()
	except urllib2.HTTPError as e:
		print '> ' + str(e.code) + ' - ' + e.reason
		print e.read()
	except ValueError as e:
		print e
    
    def retFile(self, routeStr, file):
	if routeStr[0]=='/':
		routeStr = routeStr[1:]
	try:
		request = urllib2.Request(self.rest_endpoint+'/'+routeStr)
        	request.add_header('Authorization', ' Bearer '+ self.token)
        	req = urllib2.urlopen(request)
		with open(file, 'wb') as fp:
			shutil.copyfileobj(req,fp)
	except Exception as e:
		print e

    def delete(self, routeStr):
	if routeStr[0]=='/':
                routeStr = routeStr[1:]
        try:
		request = urllib2.Request(self.rest_endpoint+'/'+routeStr)
        	request.add_header('Authorization', ' Bearer '+ self.token)
        	request.get_method = lambda: 'DELETE'
		response = urllib2.urlopen(url=request).read()
		data = json.loads(response)
        	self.response = data
		self.show()
	except urllib2.HTTPError as e:
		print '> ' + str(e.code) + ' - ' + e.reason
		print e.read()

    def put(self, routeStr, data):
        if routeStr[0]=='/':
                routeStr = routeStr[1:]
	try:
        	request = urllib2.Request(self.rest_endpoint+'/'+routeStr)
        	request.add_header('Authorization', ' Bearer '+ self.token)
        	request.get_method = lambda: 'PUT'
		enc_data = urllib.urlencode(data)
        	response = urllib2.urlopen(url=request, data=enc_data).read()
        	data = json.loads(response)
        	self.response = data
		self.show()
	except urllib2.HTTPError as e:
		print '> ' + str(e.code) + ' - ' + e.reason
		print e.read()

    def users(self):
        self.get('/v1/users');
        self.show();

