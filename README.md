# Load-Local-Data-by-LDAP-guid---EXAMPLE

# What is it
This is an example of code that would be used to load user data from a system_users database. The database connections done by first creating a PDO object with the connection information, db username and db password outside the User class, likely in some sort of connection class.

Once the object is created, we declare functions such as prepareQuery in the connection class, for example: 	public function prepareQuery($query) 	{ return self::$pdo->prepare($query); }. This is done for prepare and exec

After that we declare a function loadLocalDataByGuid which takes a $guid parameter. This parameter is the active directory GUID (globally unique identifier).

A different class that extends User would then call loadLocalDataByGuid() to activate it. This would be run in a sessionHandler after the AD info is successfully queried and before the logged in session is written to the database

If a system_users account is pulled up, then the Data can be used and assigned as needed. Otherwise, this code will create one. Once that new user is created, a quick query gets the new system_user's system_user_id. This is then used to insert a record into the system_user_to_role linking table. This table data is then used to handle app permissions later on.
