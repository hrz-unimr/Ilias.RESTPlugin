import ConfigParser
import json
import urllib
import urllib2
import shutil
import poster
import datetime

class IShell:
    """  
    ILIAS-Shell
    For personalized and administrative operations on your ILIAS LMS.
    v.2.0
    """

    def __init__(self, quite=False, connect=True):
        settings = ConfigParser.ConfigParser();
        settings.read('ishell.ini')
        self.api_key = settings.get('Authentification','api_key')
        self.username = settings.get('Authentification','username')
        self.userpass = settings.get('Authentification','password')
        self.oauth2_endpoint = settings.get('Authentification','oauth2_endpoint')
        self.ilias_client_id = settings.get('System','ilias_client_id')
        self.rest_endpoint = settings.get('System','rest_endpoint')
	self.quite = quite
	if connect == True:
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
        try:
		response = urllib2.urlopen(self.rest_endpoint+self.oauth2_endpoint, params).read()
		data = json.loads(response)
		self.token = data['access_token']
	except urllib2.HTTPError as e:
		print '> ' + str(e.code) + ' - ' + e.reason
		self.error = e.read();
		self.showError();
		self.token = ''

    def connect(self):
        self.getToken()
	#f = open('token', 'w')
	#f.write('header="Authorization: Bearer ' + tokenstr + '"')
	#f.close()
	if self.quite==False:
        	print 'Welcome to the ILIAS-Shell'
		print '> Connected with host: ' + self.rest_endpoint
		print '> Retrieved OAuth2 token: ' + self.token[1:15] + '...'
		print '> Using api-key: ' + self.api_key   

    def printIntro(self):
        print '--'
        print 'Hint: to get started just type i.<tab> to see some functions.'
        print 'Example: i.getRoutes(), see variable i.response afterwards'
   
    def getRoutes(self):
	"""Lists all available routes on the system."""
        response = urllib2.urlopen(self.rest_endpoint+'/v2/util/routes').read()
        data = json.loads(response)
        self.response = data
        self.show()

    def show(self):
	"""Pretty printing of a json response."""
        print(json.dumps(self.response, indent=2))
  
    def showError(self):
	"""Pretty printing the error responses."""
	print(json.dumps(json.loads(self.error), indent=2))	
   
    def dump(self, jsondata):
        f = open('dump.json','w')
        f.write(json.dumps(jsondata, indent=2))
        f.close
   
    def upload(self, filename, parent_ref_id, title='default', description='default'):
	""" Uploads a file to an ILIAS container specified by its ref_id.
	    Note: this operation can only be performed by admin users.
        """
	routeStr = 'admin/files'
	try:
		if title == "default":
			title = filename
		if description == "default":
			description = ""
		
		opener = poster.streaminghttp.register_openers()
		datagen, headers = poster.encode.multipart_encode({"uploadfile": open(filename, "rb"), 'ref_id':parent_ref_id, 'title':title, 'description':description})	

		request = urllib2.Request(self.rest_endpoint+'/'+routeStr, datagen, headers)
        	request.add_header('Authorization', ' Bearer '+ self.token)
        	response = urllib2.urlopen(url=request).read()
        	data = json.loads(response)
        	self.response = data
		self.show()
	except urllib2.HTTPError as e:
		print '> ' + str(e.code) + ' - ' + e.reason
		self.error = e.read();
		self.showError();
		#print e.read()


    def uploadToMyFilespace(self, filename, title='default', description='default'):
	""" Uploads a file to the personal file space of the authenticated user.
        """
	routeStr = 'v1/m/myfilespaceupload'
	try:
		if title == "default":
			title = filename
		if description == "default":
			description = ""
		
		opener = poster.streaminghttp.register_openers()
		datagen, headers = poster.encode.multipart_encode({"uploadfile": open(filename, "rb")})	

		request = urllib2.Request(self.rest_endpoint+'/'+routeStr, datagen, headers)
        	request.add_header('Authorization', ' Bearer '+ self.token)
        	response = urllib2.urlopen(url=request).read()
        	data = json.loads(response)
        	self.response = data
		self.show()
	except urllib2.HTTPError as e:
		print '> ' + str(e.code) + ' - ' + e.reason
		self.error = e.read();
		self.showError();
		#print e.read()


    def post(self, routeStr, data):
	"""Sends a POST request to the specified route and with the data.
            Example call:
		i.post('clients',{'api_key':'testing','api_secret':'1234','oauth2_gt_resourceowner_active':'1'})
	"""
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
		self.error = e.read();
		self.showError();
		#print e.read()

    def get(self, routeStr):
	"""Sends a GET request to the specified route.
	   Note: in this version, optional data can be send by appending them to the route,
	   e.g. ?key1=val1&key2=val2&...
	"""
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
		self.error = e.read();
		self.showError();
		#print e.read()
	except ValueError as e:
		print e
    
    def download(self, refID, fileName="default"):
	"""Retrieves a binary file from ILIAS (i.e. an object of type file) 
	   and stores it to the current folder.
	   The requested file will be stored under the filename provided by 
	   the argument "fileName".
	"""
	if fileName=="default":
		print "No filename provided. Try to fetch meta-data..."
		self.get('v1/files/' + str(refID)+ '?meta_data=1');
		fileName = self.response['name'];
		print "Saving object as ... "+ fileName

	routeStr = 'v1/files/' + str(refID)
	try:
		request = urllib2.Request(self.rest_endpoint+'/'+routeStr)
        	request.add_header('Authorization', ' Bearer '+ self.token)
        	req = urllib2.urlopen(request)
		with open(fileName, 'wb') as fp:
			shutil.copyfileobj(req,fp)
	except Exception as e:
		print e


    def downloadCourseExportFile(self, refID, fileName):
	"""Retrieves a course export file (Zip) from ILIAS and stores it to 
	   the current folder.
	"""
	routeStr = 'v1/courses/export/download/' + str(refID)
	try:
        	enc_data = urllib.urlencode({'filename':fileName});
		request = urllib2.Request(self.rest_endpoint+'/'+routeStr+'?'+enc_data)
        	request.add_header('Authorization', ' Bearer '+ self.token)
        	req = urllib2.urlopen(request)
		with open(fileName, 'wb') as fp:
			shutil.copyfileobj(req,fp)
		print "Saving object as ... "+ fileName
	except Exception as e:
		print e

    def delete(self, routeStr):
	""" Sends a DELETE request.
	    Example call:
		i.delete('clients/5');
	"""
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

    def delete(self, routeStr, dict):
	""" Sends a DELETE request with additional data.
	    Example call:
		i.delete('v1/desktop/overview',{"ref_id":61})
	"""	
        if routeStr[0]=='/':
                routeStr = routeStr[1:]
	try:
        	request = urllib2.Request(self.rest_endpoint+'/'+routeStr)
        	request.add_header('Content-Type','application/json');
		request.add_header('Authorization', ' Bearer '+ self.token)
        	request.get_method = lambda: 'DELETE'
		#enc_data = urllib.quote_plus(data)
        	jsondata = json.dumps(dict)
		post_data = jsondata.encode('utf-8')
	
		response = urllib2.urlopen(url=request, data=jsondata).read()
        	data = json.loads(response)
        	self.response = data
		self.show()
	except urllib2.HTTPError as e:
		print '> ' + str(e.code) + ' - ' + e.reason
		print e.read()

    def put(self, routeStr, dict):
	""" Sends a PUT request with JSON data which is specified by a python dict.
	    Example call:
		i.put('clients/5',{"permissions":[{"pattern":"/routes","verb":"GET"}]});
	"""
        if routeStr[0]=='/':
                routeStr = routeStr[1:]
	try:
        	request = urllib2.Request(self.rest_endpoint+'/'+routeStr)
        	request.add_header('Content-Type','application/json');
		request.add_header('Authorization', ' Bearer '+ self.token)
        	request.get_method = lambda: 'PUT'
		#enc_data = urllib.quote_plus(data)
        	jsondata = json.dumps(dict)
		post_data = jsondata.encode('utf-8')
	
		response = urllib2.urlopen(url=request, data=jsondata).read()
        	data = json.loads(response)
        	self.response = data
		self.show()
	except urllib2.HTTPError as e:
		print '> ' + str(e.code) + ' - ' + e.reason
		print e.read()
    
    def putJSONs(self, routeStr, jsonstring):
	""" Sends a PUT request with JSON data which is specified as string.
	    Example call:
		i.putJSONs('clients/5','{"permissions":[{"pattern":"/routa","verb":"GET"}]}');
	"""
        if routeStr[0]=='/':
                routeStr = routeStr[1:]
	try:
        	request = urllib2.Request(self.rest_endpoint+'/'+routeStr)
        	request.add_header('Content-Type','application/json');
		request.add_header('Authorization', ' Bearer '+ self.token)
        	request.get_method = lambda: 'PUT'
		#enc_data = urllib.quote_plus(data)
        	jsondata = json.dumps(json.loads(jsonstring))
		post_data = jsondata.encode('utf-8')
	
		response = urllib2.urlopen(url=request, data=jsondata).read()
        	data = json.loads(response)
        	self.response = data
		self.show()
	except urllib2.HTTPError as e:
		print '> ' + str(e.code) + ' - ' + e.reason
		print e.read()

    def users(self):
	""" Lists all users of the system """
        self.get('/v1/users');
        self.show();
    def toDate(self, timestamp):
	""" Converts a timestamp string into date format """
	print(
	    datetime.datetime.fromtimestamp(int(timestamp)).strftime('%Y-%m-%d %H:%M:%S')
	)	

