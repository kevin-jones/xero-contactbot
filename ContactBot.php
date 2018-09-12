<?php

use \Html2Text\Html2Text;
use XeroPHP\Application\PrivateApplication;
use XeroPHP\Models\Accounting\Address;
use XeroPHP\Models\Accounting\Contact;
use XeroPHP\Models\Accounting\Phone;

class ContactBot {
    public $url;
    public $data;
    public $selections;
    public $fields = [
        'company' => null,
        'address1' => null,
        'address2' => null,
        'address3' => null,
        'tel' => null,
        'email' => null,
        'postcode' => null,
    ];
    public $address = null;

    public function __construct($url = null) {
        if (!empty($url) && filter_var($url, FILTER_VALIDATE_URL)) {
            $this->url = $url;
            $this->load();
            $this->findTelephone();
            $this->findEmail();
            $this->findPostcode();
            $this->findAddress();
        }
    }

    public function load()
    {
        if (false) {
            $data = file_get_contents('cached.html');
        } else {
            $ch = curl_init();
            $timeout = 5;
            curl_setopt($ch, CURLOPT_URL, $this->url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch,CURLOPT_ENCODING , "");
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
            $data = curl_exec($ch);
            curl_close($ch);
        }

        $data = Html2Text::convert($data, true);
        $data = preg_replace('/[\r\n]+/', " ", $data);
        $data = preg_replace('/[^0-9A-Za-z,|]{2,}/', " ", $data);
        $this->data = $data;
    }

    public function loadSelections($data)
    {
        if (!empty($data['url'])) {
            $this->url = $data['url'];
            $this->load();

            $fields = ['tel', 'email', 'postcode'];

            foreach ($fields as $field) {
                if (isset($data['selections'][$field])) {
                    $value = $data['values'][$field][$data['selections'][$field]];
                    $this->fields[$field] = $value;
                }
            }

            $this->address = $data['values']['address'][$data['selections']['address']];
            $this->parseAddress();
        }
    }

    public function createContact($data, $config)
    {
        $xero = new PrivateApplication([
            'oauth' => [
                'callback' => 'https://control.development.shopblocks.com/callbacks/xero',

                'consumer_key' => $config[ENVIRONMENT]['keys']['public'],
                'consumer_secret' => $config[ENVIRONMENT]['keys']['public'],

                'rsa_private_key' => file_get_contents($config[ENVIRONMENT]['keys']['rsa_private_key']),
                'rsa_public_key' => file_get_contents($config[ENVIRONMENT]['keys']['rsa_public_key']),
            ]
        ]);

        $contact = new Contact($xero);
        $data = $data['contact'];

        if (!empty($data['company'])) {
            $contact->setName($data['company']);
        }

        if (!empty($data['email'])) {
            $contact->setEmailAddress($data['email']);
        }

        if (!empty($data['tel'])) {
            $phone = new Phone($xero);
            $phone->fromStringArray([
                'PhoneType' => 'DEFAULT',
                'PhoneNumber' => $data['tel'],
            ]);
            $contact->addPhone($phone);
        }

        if (!empty($data['address1'])) {
            $address = new Address($xero);
            $address->fromStringArray([
                'AddressType' => 'STREET',
                'AddressLine1' => $data['address1'],
                'AddressLine2' => $data['address2'],
                'City' => $data['address3'],
                'PostalCode' => $data['postcode'],
            ]);
            $contact->addAddress($address);
        }

        if (!empty($data['supplier'])) {
            $contact->setIsSupplier(true);
        } else {
            $contact->setIsSupplier(false);
        }

        if (!empty($data['customer'])) {
            $contact->setIsCustomer(true);
        } else {
            $contact->setIsCustomer(false);
        }

        if (!empty($data['website'])) {
            $contact->setWebsite($data['website']);
        }

        $this->response = $contact->save();
        $this->status = $this->response->getStatus();

        if ($this->status != 200) {
            $this->debug("Error adding contact with status code {$this->status}"); 
        }

        $this->contact = $contact;
        // $this->debug($this);
    }

    private function parseAddress($address = '')
    {
        $parts = preg_split('/[^A-Z\s0-9-]/i', $this->address);
        $parts = array_map('trim', $parts);

        $fields = ['company', 'address1', 'address2', 'address3'];
        foreach ($fields as $index => $field) {
            if (isset($parts[$index])) {
                $this->fields[$field] = $parts[$index];
            }
        }
    }

    public function findTelephone()
    {
        $regex = '/[0-9-\(\)\+]{2,}\s+[0-9-\(\)\+]{2,}\s*[0-9\s-\(\)\+]{2,10}/';

        if (preg_match_all($regex, $this->data, $matches)) {
            $uniques = array_values(array_unique($matches[0]));
            $this->fields['tel'] = $uniques;
        }
    }

    public function findEmail()
    {
        $regex = '/[a-z0-9_\-\+\.]+@[a-z0-9\-]+\.[a-z]{2,4}(?:\.[a-z]{2})?/i';

        if (preg_match_all($regex, $this->data, $matches)) {
            $uniques = array_values(array_unique($matches[0]));
            $this->fields['email'] = $uniques;
        }
    }

    public function findPostcode()
    {
        $regex = '/[A-Z]{1,2}[0-9]{1}[A-Z0-9]*\s+[0-9]{1}[A-Z]{2}/i';

        if (preg_match_all($regex, $this->data, $matches)) {
            $uniques = array_values(array_unique($matches[0]));
            $this->fields['postcode'] = $uniques;
        }
    }

    public function findAddress()
    {
        // find a postcode and grab up to 100 characters before it
        $regex = '/.{1,100}[A-Z]{1,2}[0-9]{1}[A-Z0-9]*\s+[0-9]{1}[A-Z]{2}/si';

        if (preg_match_all($regex, $this->data, $matches)) {
            $uniques = array_values(array_unique($matches[0]));
            foreach ($uniques as $key => $address) {
                $uniques[$key] = preg_replace("/[\s\r\n]*[|]+[\s\r\n]*/", ", ", $address);
            }
            $this->address = $uniques;
        }
    }

    private function debug($object)
    {
        echo '<pre style="background:#ddd;color:#000;padding:10px">' . print_r($object, 1) . '</pre>';
    }

    public function viewData()
    {
        echo '<div><textarea style="width:70%;height:300px">'.print_r($this->data,1).'</textarea></div>';
    }
}

