<?php
class User {
	//

	// Given a GUID string, fetches that user's data from the local SQL database.
	// We use the GUID in case a user with username 'foo' comes and goes, then someone ELSE
	// with the same name (and resultant 'foo' username') has a new account set up.
	private function loadLocalDataByGuid($guid) {
		// Query database for user
		$info_sth = $this->core->prepareQuery(
		"SELECT system_user_id,
			username,
			firstName,
			lastName,
			email,
			active,
			ldap_objectguid,
			case_man
	 	FROM system_users
	 	WHERE ldap_objectguid=?
	    AND active=1;"
		);
		$info_result = $info_sth->execute( array($guid) );


		# If user does not exist in ils system_users table and search query was able to prepare, create that new user here
		if( ( $info_sth->rowCount() != 1) && ( $info_result ) ){
			// Prepare query for entry into database
			$new_user_info_sth = $this->core->prepareQuery(
				"INSERT INTO `system_users`
				(
					system_role_id,
					username,
					firstName,
					lastName,
					email,
					ldap_objectguid,
					current
				) VALUES (
					?, # 'system_role_id' - Set to default of 1 Default Role
					?, # 'username'
					?, # 'firstName'
					?, # 'lastName'
					?, # 'email'
					?, # 'ldap_objectguid'
					?  # 'current' - Set to default of 7. No db default set.
				)"
			);


			// Execute query to add new user
			$new_user_info_sth_result = $new_user_info_sth->execute( array(
					1,
					$this->ldap_data['samaccountname'][0],
					$this->ldap_data['givenname'][0],
					$this->ldap_data['sn'][0],
					$this->ldap_data['mail'][0],
					$guid,
					7
				) );


			// Throw error if insert fails
			if( !$new_user_info_sth_result ) throw new Exception( 'Query to add new user to ILS system failed.' );


			// Prepare query for getting new user's system_user_id
			$system_user_id_get_prep = $this->core->prepareQuery(
					"SELECT
						system_user_id
				 	FROM
						system_users
				 	WHERE
						ldap_objectguid=?
				  AND active=1;"
			);


			// If query to add new user is sucessful, execute query to get that new user's system_user_id
			if( $new_user_info_sth_result ){
				$system_user_id_get_result = $system_user_id_get_prep->execute( array( $guid ) );
			};


			// Throw error get system_user_id query fails
			if( !$system_user_id_get_result ) throw new Exception('New user ID Query Failed');
			if( $system_user_id_get_prep->rowCount() != 1 ) throw new Warning('Query to get system user ID for new user failed.');


			// Get new system user id and set to variable
			$system_user_result_array	=	$system_user_id_get_prep->fetch(PDO::FETCH_ASSOC);
			$new_system_user_id				= $system_user_result_array['system_user_id'];


			// If $system_user_id_get_result is true, then we add a record to system_user_to_role so that permissions work
			if( $system_user_id_get_result ){
				// Prepare query for entry into database
				$new_user_to_role_sth = $this->core->prepareQuery(
					"INSERT INTO `system_user_to_role`
					(
							system_user_id,
							system_role_id
					) VALUES (
						?, 	# 'system_user_id' -
						?		# 'system_role_id' - Set to default of 7 Default Role. No db default set.
					)"
				);
			}


			// Execute query to add new system_user_to_role record so permissions work
			$new_user_to_role_sth_result = $new_user_to_role_sth->execute( array(
				$new_system_user_id, 	# 'system_user_id'
				7		# 'system_role_id'
			) );


			// Throw error get system_user_id query fails
			if( !$new_user_to_role_sth_result ) throw new Exception('Query to add system user to role record failed');
			if( $new_user_to_role_sth->rowCount() != 1 ) throw new Warning('Query to add system user role failed.');


			// Once data is added, take the results and set equal $info_sth.
			$info_sth 		= $new_user_info_sth;


			// By doing this, the code should continue on with the new information as if it was a user that already existed
			return $info_sth;
		}// End if function to handle adding a new system user


		# Fetch data & store
		$data = $info_sth->fetch(PDO::FETCH_ASSOC);
		if($data === false) throw new Exception('Could not fetch user data');
		$this->sql_data = $data;

		// Here we do stuff with the local user data we've pulled

		return true;
	}// End loadLocalDataByGuid

	//.....Other code

}
?>
