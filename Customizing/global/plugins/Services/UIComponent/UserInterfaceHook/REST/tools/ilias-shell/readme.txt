ILIAS-Shell
-----------
The ILIAS-Shell is useful for performing personalized and administrative operations based on IPython.
D.Schaefer <schaefer@hrz.uni-marburg>, 2015, v.1.5

Install:
Just configure the file ishell.py and start by tying run.sh on the command line.

Alternatively: start by typing ipython on the command line as described below.

Examples:
$ routes=i.getRoutes()
$ i.get('/admin/describe/64');
$ i.post('v1/users',{'login':'isabell','passwd':'top_secret','firstname':'isa','lastname':'bell','email':'testing@localhost','gender','f'})

