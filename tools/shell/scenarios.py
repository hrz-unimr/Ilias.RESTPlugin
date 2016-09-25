import ishell

class Scenarios:
    """
     ILIAS Scenarios
     - for testing and benchmark purposes.
     v.0.1
    """

    def __init__(self):
	self.i = ishell.IShell(True,True)
	self.api_key = 'thessaloniki'

    def printIntro(self):
	print 'Welcome to ILIAS Scenarios'
	print '===================================================================='
	print 'You can list the available scenarios by typing s. followed by <tab>.'
	print 'Example: ?s.buildThessaloniki would describe what it does and '
	print 's.buildThessaloniki() would actually create this scenario.'

    def buildThessaloniki(self):
	"""
	 Constructs the same scenario as described in testing/api/scenarios
		- Creates a new API-Key / REST Client "testing"
		- Adds permissions to "testing" for /v1/users POST and DELETE
		- Creates two system users (Hero and Leander)
		- Creates a new Course
		- Adds the authenticated user to the Course
		- Adds file (logo.png) to test course 1
	"""
	print 'Creating new API-Key'
	self.i.post('clients',{'api_key':self.api_key,'api_secret': 'thessaloniki','oauth2_gt_resourceowner_active': '1'})
	self.testclientid = self.i.response['id']
	print 'Setting up route permissions for API-Key thessaloniki'
	self.i.post('clientpermissions',{'api_key':self.api_key, 'pattern':'/v1/courses', 'verb':'GET'})	
	self.i.post('clientpermissions',{'api_key':self.api_key, 'pattern':'/v1/courses', 'verb':'POST'})
	self.i.post('clientpermissions',{'api_key':self.api_key, 'pattern':'/v1/courses/:ref_id', 'verb':'GET'})
	self.i.post('clientpermissions',{'api_key':self.api_key, 'pattern':'/v1/courses/:ref_id', 'verb':'DELETE'})
	self.i.post('clientpermissions',{'api_key':self.api_key, 'pattern':'/v1/courses/enroll', 'verb':'POST'})
	self.i.post('clientpermissions',{'api_key':self.api_key, 'pattern':'/v1/courses/join/:ref_id', 'verb':'GET'})
	self.i.post('clientpermissions',{'api_key':self.api_key, 'pattern':'/v1/courses/leave/:ref_id', 'verb':'GET'})
	self.i.post('clientpermissions',{'api_key':self.api_key, 'pattern':'/admin/files', 'verb':'POST'})
	self.i.post('clientpermissions',{'api_key':self.api_key, 'pattern':'/v1/users', 'verb':'POST'})
	self.i.post('clientpermissions',{'api_key':self.api_key, 'pattern':'/v1/users/:user_id', 'verb':'DELETE'})
	self.testclient = ishell.IShell(True,False)
	self.testclient.api_key = self.api_key
	self.testclient.connect()
	# Create test users
	print 'Creating ILIAS users for Scenario Thessaloniki'
	self.testclient.post('/v1/users',{'login':'hero','passwd':'stormy', 'firstname':'Hero', 'lastname' : 'Sestos', 'email' : 'myth@localhost','gender':'f'});
	self.testuser1_id = self.testclient.response['id']
	self.testclient.post('/v1/users',{'login':'leander','passwd':'stormy', 'firstname':'Leander', 'lastname' : 'Sestos', 'email' : 'myth@localhost','gender':'m'});
	self.testuser2_id = self.testclient.response['id']
	# Create test course
	print 'Creating an ILIAS Course for Sceneario Thessaloniki'
	self.testclient.post('/v1/courses',{'ref_id':'1','title':'A Course in Thessaloniki', 'description' : 'Created by IShell - Scenarios (ILIAS RESTPlugin)'});
	self.testcourse_refId = self.testclient.response['refId']
	# Upload file into course
	self.testclient.upload('upload/logo.png', self.testcourse_refId)
	print "Done"


