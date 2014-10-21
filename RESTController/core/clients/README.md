Clients API for the ILIAS REST Plugin
======================================
This is an integral part of the [ILIAS REST Plugin](https://github.com/eqsoft/RESTPlugin).

#### Features:
* Enables the management of REST clients.

#### Parameters:
<thead>
        <th>Endpoint</th>
        <th>Verb</th>
        <th>Parameters</th>
        <th>Description</th>
    </thead>
    <tbody>
        <tr>
            <td>/clients</td>
            <td>GET</td>
            <td></td>
            <td>Retrieves a list of all REST clients</td>
        </tr>
        <tr>
        <td>/clients</td>
        <td>POST</td>
        <td>['data']['client_id'], ['data']['client_secret'], ['data']['oauth_consent_message'], ['data']['redirection_uri'], ['data']['permissions']</td>
        <td>Creates a new REST client</td>
        </tr>
        <tr>
            <td>/clients/:id</td>
            <td>PUT</td>
            <td>['data']['client_id'], ['data']['client_secret'], ['data']['oauth_consent_message'], ['data']['redirection_uri'], ['data']['permissions']</td>
            <td>Updates a client specified by :id</td>
        </tr>
        <tr>
            <td>/clients/:id</td>
            <td>DELETE</td>
            <td></td>
            <td>Deletes a REST client specified by :id</td>
        </tr>

    </tbody>
Examples
---------

#### History:
v0.1 - 2014-06

##### Authors:
Dirk Schaefer <schaefer at hrz.uni-marburg.de>

