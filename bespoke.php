/* This macro will be parsed as PHP code (see http://www.php.net)

The calculateshipping function is called every time a shipping calculation request is made by Shopify.

The function must return an array of available shipping options, otherwise no shipping options will be returned to your customers.
*/
function calculateshipping($DATA) {

	/* do not edit above this line */

    $_RATES = array();

	# if ($DATA['destination']['country'] != 'US') return array();

    $_USPS_USERNAME = 'xxxxxxxxxxxx';
    $_USPS_PASSWORD = 'xxxxxxxxxxxx';
    $_USPS_CONTRACTTYPE = 'C'; //N=none(retail), C=commercial, P=commercial plus

    $_UPS_ACCESS = "xxxxxxxxxxxxxxxx";
    $_UPS_USERID = "";
    $_UPS_PASSWD = "";
	$_UPS_ACCOUNT = null;

	$isresidential = true;
    if ($DATA['destination']['company_name'] !== null && $DATA['destination']['company_name'] != '') $isresidential = false;

	$mustUseUPS = false;
	$w = 0;
	$vendors = ['Invidia', 'Perrin', 'Rally Armor', 'T14', 'Tein', 'Air Lift', 'Husky Liners', 'Go Fast Bits'];
	foreach ($DATA['items'] as $i) {
		$w += $i['quantity']*round($i['grams']/1000*2.20462,2);
		foreach($vendors as $vendor) {
		    if ($i['vendor'] == $vendor) $mustUseUPS = true;
		}
	}
	
	// checks to see if there's a seibon itme in cart
	$seibon = false;
	foreach ($DATA['items'] as $i) {
	    if ($i['vendor'] == 'Seibon Carbon') {
	        $seibon = true;
	        $rota = false;
	        $bavar = false;
	    }
	}
	
	// checks to see if there's a rota item in cart
	$rota = false;
	foreach ($DATA['items'] as $i) {
	    if ($i['vendor'] == 'Rota') {
	        $rota = true;
	        $seibon = false;
	        $bavar = false;
	    }
	}
	
	// checks to see if there's a bavar item in cart
	$bavar = false;
	foreach ($DATA['items']as $i) {
	    if ($i['vendor'] == 'Bavar Racing') {
	        $bavar = true;
	        $rota = false;
	        $seibon = false;
	    }
	}

	$packages = array(['length'=>12, 'width'=>8.75, 'height'=>6, 'weight'=>$w+0.4125]);
    
    // sets shipping rate to seibon's flat rate
    if ($seibon) {
        $_RATES[] = array(
            "service_name" => 'Seibon Flat Rate',
            "service_code" => 'Seibon Flat Rate',
            "total_price" => 150*100,
            "currency" => "USD",
        );
    } 
    
    // sets shipping rate to rota's flat rate
    else if ($rota) {
        $_RATES[] = array(
            "service_name" => 'Rota Flat Rate',
            "service_code" => 'Rota Flat Rate',
            "total_price" => 100*100,
            "currency" => "USD",
        );
    }
    
    // sets shipping rate to rota's flat rate
    else if ($bavar) {
        $_RATES[] = array(
            "service_name" => 'Bavar Racing Flat Rate',
            "service_code" => 'Bavar Racing Flat Rate',
            "total_price" => 180*100,
            "currency" => "USD",
        );
    }
    
	//select origin address
	else if ($mustUseUPS && !$seibon && !$rota && !$bavar) {
		$ups = new UPSAPI($_UPS_USERID,$_UPS_PASSWD,$_UPS_ACCESS,$_UPS_ACCOUNT);
		$ups->setDestination($DATA['destination']['city'],$DATA['destination']['province'],$DATA['destination']['postal_code'],$DATA['destination']['country'],$isresidential);
		$cartTotal = 0;
		$freeShippingTreshold = 250*100;
		foreach ($DATA['items'] as $item) {
		    $cartTotal += $item['quantity']*$item['price'];
		}
        if (in_array($DATA['destination']['province'],array('WA', 'OR', 'CA', 'ID', 'NV', 'MT', 'WY', 'UT', 'AZ', 'CO', 'NM', 'TX', 'ND', 'SD', 'NE', 'KS', 'OK', 'MN', 'IA', 'MO', 'AR', 'LA', 'MI', 'IN', 'KY', 'TN', 'MS', 'AL', 'ME', 'NH', 'VT', 'MA', 'NY', 'RI', 'CT', 'PA', 'NJ', 'DE', 'MD', 'WV', 'VA', 'NC', 'SC', 'GA', 'FL'))) {
            if ($cartTotal > $freeShippingTreshold) {
                $_RATES[] = array(
                    "service_name" => 'Free Shipping',
                    "service_code" => 'Free Shipping',
                    "total_price" => 0,
                    "currency" => "USD",
                );
            }
        }
		
		if (in_array($DATA['destination']['province'],array('AL','AR','CT','DE','FL','GA','IL','IN','IA','KY','LA','ME','MD','MA','MI','MN','MS','MO','NH','NJ','NY','NC','OH','PA','RI','SC','TN','VT','WV','WI'))) {
			$ups->setOrigin('Philadelphia','PA','19092','US');
		} else {
			$ups->setOrigin('Reno','NV','89501','US');
		}
		$ups->getTransitTime = true;
		$r = $ups->getRate($packages,'IMPERIAL');
		if ($r) {
			foreach ($r as $_rc => $_r) {
				if (preg_match('/A.M./',$_r['name'])) continue;
				$_RATES[] = array(
					"service_name" => $_r['name'],
					"service_code" => $_r['code'],
					"total_price" => $_r['amount']*100*0.65,
					"currency" => "USD",
				);
			}
		}
	} else {
		//use USPS
		$usps = new USPSAPI($_USPS_USERNAME,$_USPS_PASSWORD,$_USPS_CONTRACTTYPE);
		$usps->setOrigin(84165);
		$usps->setDestination($DATA['destination']['province'],substr($DATA['destination']['postal_code'],0,5),$DATA['destination']['country'],$isresidential);
		$r = $usps->getRate($packages);
		$country = $DATA['destination']['country'];
        $productsInCollection = getCollection(315773633);
        $restrictedItems = getCollection(315774721);
        $isProductInCollection = false;
        $containsRestricted = false;
        foreach($DATA['items'] as $item) {
            if(!in_array($item['product_id'],$productsInCollection)) $containsRestricted = true;
            if(in_array($item['product_id'],$productsInCollection)) $isProductInCollection = true;
        }
        if (!$containsRestricted && $isProductInCollection) {
            if ($country == "US") {
                $_RATES[] = array(
		        "service_name" => "Sticker Flat Rate (No Insurance)",
		        "service_code" => "Flate Rate Sticker",
		        "total_price" => 100,
		        "currency" => "USD",
		    );
            } else {
                $_RATES[] = array(
		        "service_name" => "International Sticker Flat Rate (No Insurance)",
		        "service_code" => "Flate Rate Sticker",
		        "total_price" => 200,
		        "currency" => "USD",
		    );
            }
        }

		if ($r) {
			foreach ($r as $_rc => $_r) {
				if (preg_match('/hold|holiday|flat|regional|parcel|envelopes|media|library/i',$_r['name'])) continue;
				$_r['name'] = preg_replace('/ \d-Day/i','',$_r['name']);
				$service = $_r['name'];
			    $firstClass = "First-Class";
			    if(strpos($service, $firstClass) !== false) {
				    $service = $_r['name'] . " (Recommended)";
			    }
				$_RATES[] = array(
					"service_name" => 'USPS '.$service,
					"service_code" => $_r['code'],
					"total_price" => $_r['amount']*100,
					"currency" => "USD",
				);
			}
		}

	}

	return $_RATES;

	/* do not edit below this line */

}