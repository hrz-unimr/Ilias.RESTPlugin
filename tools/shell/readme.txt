ILIAS-Shell
-----------
The ILIAS-Shell is useful for performing personalized and administrative operations based on IPython.
D.Schaefer <schaefer@hrz.uni-marburg>, 2015

Requirements:
IPython and Python Poster Lib

[On Ubuntu/Debian]
sudo apt-get install ipython
sudo apt-get install python-poster

[Using Python Package Index]
pip install ipython
pip install poster

Install:
Just configure the file ishell.ini (cp ishell.ini.default ishell.ini and edit ishell.ini).
Start by tying run.sh on the command line (tested with bash).

Examples:
$ routes=i.getRoutes()
$ i.get('/admin/describe/64');
$ i.post('v1/users',{'login':'isabell','passwd':'top_secret','firstname':'isa','lastname':'bell','email':'testing@localhost','gender':'f'})
