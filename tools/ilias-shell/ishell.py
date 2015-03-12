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

    def getToken(self):
        params = urllib.urlencode({'grant_type': 'password', #Invoke OAuth2 mechanism via "resources owner credentials".
              'api_key': self.api_key,
        'username': self.username,
        'password': self.userpass,
        'client_id': self.api_key,
        'ilias_client_id' : self.ilias_client_id
        })
        response = urllib2.urlopen(self.rest_endpoint+self.oauth2_endpoint, params).read()
        data = json.loads(response)
        self.token = data['access_token']


    def connect(self):
        self.getToken()
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
  
    def post(self, routeStr, data):
#response = urllib2.urlopen(self.rest_endpoint+'/'+routeStr).read()
        request = urllib2.Request(self.rest_endpoint+'/'+routeStr)
        request.add_header('Authorization', ' Bearer '+ self.token)
        enc_data = urllib.urlencode(data)
        response = urllib2.urlopen(url=request, data=enc_data).read()
#response
        data = json.loads(response)
        self.latestResponse = data
        return data

    def get(self, routeStr, firstAttempt=True):
#response = urllib2.urlopen(self.rest_endpoint+'/'+routeStr).read()
        request = urllib2.Request(self.rest_endpoint+'/'+routeStr)
        request.add_header('Authorization', ' Bearer '+ self.token)
        response = urllib2.urlopen(request).read()
#response
        data = json.loads(response)
        if data['msg'] == 'Token expired.' and firstAttempt:
            self.getToken()
            return self.get(routeStr, False)
        else: 
            self.latestResponse = data
            return data

    def users(self):
        self.get('/v1/users');
        self.show();
