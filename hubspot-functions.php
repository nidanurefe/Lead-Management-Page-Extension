<?php
function createHubSpotCompany($companyName, $companyAddress, $companyPhone, $companyEmail) {
    $url = 'https://api.hubapi.com/crm/v3/objects/companies';
    $bearerToken = 'ENTER_YOUR_BEARER_TOKEN_HERE';

    $data = array(
        'properties' => array(
            'name' => $companyName,
            'address' => $companyAddress,
            'phone' => $companyPhone,
            'website' => $companyEmail
        )
    );

    $jsonData = json_encode($data);

    $ch = curl_init($url);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Authorization: Bearer ' . $bearerToken
    ));

    $response = curl_exec($ch);

    if(curl_errno($ch)) {
        echo 'Error:' . curl_error($ch);
    }

    curl_close($ch);

    $result = json_decode($response, true);

    if (isset($result['id'])) {
        return $result['id']; // Return the company ID
    }

    return null;
}

function createHubSpotContact($name, $phone, $email) {
    $url = 'https://api.hubapi.com/crm/v3/objects/contacts';
    $bearerToken = 'ENTER_YOUR_BEARER_TOKEN_HERE';

    $data = array(
        'properties' => array(
            'firstname' => $name,
            'phone' => $phone,
            'email' => $email
        )
    );

    $jsonData = json_encode($data);

    // Start the curl request
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Authorization: Bearer ' . $bearerToken
    ));

    $response = curl_exec($ch);

    if(curl_errno($ch)) {
        echo 'Error:' . curl_error($ch);
    }

    curl_close($ch);

    $result = json_decode($response, true);

    if (isset($result['id'])) {
        return $result['id']; // Return the contact ID
    }

    return null;
}
function getCompanyIdByName($companyName) {
    $url = "https://api.hubapi.com/crm/v3/objects/companies/search";
    
    $bearerToken = 'ENTER_YOUR_BEARER_TOKEN_HERE';

    $postData = json_encode([
        "filterGroups" => [
            [
                "filters" => [
                    [
                        "propertyName" => "name",
                        "operator" => "EQ",
                        "value" => $companyName
                    ]
                ]
            ]
        ],
        "properties" => ["id", "name"],
        "limit" => 1
    ]);

    // Start the curl request
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        "Authorization: Bearer $bearerToken"
    ]);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        echo 'cURL Error: ' . curl_error($ch);
        return null;
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode != 200) {
        echo 'HTTP Error: ' . $httpCode;
        echo 'Response: ' . $response;
        return null;
    }

    $data = json_decode($response, true);
    
    
    if (isset($data['results']) && !empty($data['results'])) {
        return $data['results'][0]['id']; // Return the company ID
    } else {
        return null; // If company not found, return null
    }
}


function associateCompanies($parentCompanyId, $childCompanyId) {
    
    
    $bearerToken = 'ENTER_YOUR_BEARER_TOKEN_HERE';
    $hubspotApiUrl = "https://api.hubapi.com/crm/v3/associations/companies/companies/batch/create";

    
    $data = [
        'inputs' => [
            [
                'from' => ['id' => $parentCompanyId],
                'to' => ['id' => $childCompanyId],
                'type' => 'company_to_company'
            ]
        ]
    ];

    // Start the curl request
    $ch = curl_init($hubspotApiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $bearerToken,
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

    // Send the request to the API and get the response
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // Return the response
    if ($httpCode === 200) {
        return "Companies successfully associated.";
    } else {
        return "Error: " . $response;
    }
}

function associateContactWithCompany($contactId, $companyId) {
    
    
    $bearerToken = 'ENTER_YOUR_BEARER_TOKEN_HERE';
    $hubspotApiUrl = "https://api.hubapi.com/crm/v3/associations/contacts/companies/batch/create";

    
    $data = [
        'inputs' => [
            [
                'from' => ['id' => $contactId],
                'to' => ['id' => $companyId],
                'type' => 'contact_to_company'
            ]
        ]
    ];

    // Start the curl request
    $ch = curl_init($hubspotApiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $bearerToken,
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

    // Send the request to the API and get the response
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // Return the response
    if ($httpCode === 200) {
        return "Contact successfully associated with the company.";
    } else {
        return "Error: " . $response;
    }
}






?>
