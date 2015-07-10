<?php
class ModelFraudFraudLabsPro extends Model {

	private function hashIt($s, $prefix = 'fraudlabspro_') {
	  	$hash = $prefix . $s;

		for ($i = 0; $i < 65536; $i++) {
			$hash = sha1($prefix . $hash);
		}

		return $hash;
	}

	function getRequest($url) {
		$ch = curl_init();

		curl_setopt($ch, CURLOPT_FAILONERROR, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_ENCODING , 'gzip, deflate');
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);

		$result = curl_exec($ch);

		if (!curl_errno($ch)) {
			return $result;
		}

		curl_close($ch);

		return false;
	}

	public function check($data) {
		// Do not performat fraud check if FraudLabs Pro is disabled or API key is not provided.
		if (!$this->config->get('fraudlabspro_status') ||!$this->config->get('fraudlabspro_key')) {
			return;
		}

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "fraudlabspro WHERE order_id = '" . (int)$data['order_id'] . "'");

		// Do not call FraudLabs Pro API if order is already screened.
		if ($query->num_rows) {
			return;
		}

		$ip = $_SERVER['REMOTE_ADDR'];

		// Detect client IP is store is behind CloudFlare protection.
		if (isset($_SERVER['HTTP_CF_CONNECTING_IP']) && filter_var($_SERVER['HTTP_CF_CONNECTING_IP'], FILTER_VALIDATE_IP)) {
			$ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
		}

		// Get real client IP is they are behind proxy server.
		if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && filter_var($_SERVER['HTTP_X_FORWARDED_FOR'], FILTER_VALIDATE_IP)) {
			$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		}

		// Overwrite client IP if simulate IP is provided.
		if (filter_var($this->config->get('fraudlabspro_simulate_ip'), FILTER_VALIDATE_IP)) {
			$ip = $this->config->get('fraudlabspro_simulate_ip');
		}

		$risk_score = 0;

		$fraud_info = $this->getFraud($data['order_id']);

		if ($fraud_info) {
			$risk_score = (isset($fraud_info['risk_score'])) ? $fraud_info['risk_score'] : 0;
		} else {
			$request['key'] = $this->config->get('fraudlabspro_key'); 

			$request['ip'] = $data['ip']; 
			$request['bill_city'] = $data['payment_city']; 
			$request['bill_state'] = $data['payment_zone']; 
			$request['bill_country'] = $data['payment_iso_code_2']; 
			$request['bill_zip_code'] = $data['payment_postcode']; 
			$request['email_domain'] = utf8_substr(strrchr($data['email'], '@'), 1); 
			$request['phone'] = $data['telephone']; 

			if ($data['shipping_method']) { 
				$request['ship_addr'] = $data['shipping_address_1']; 
				$request['ship_city'] = $data['shipping_city']; 
				$request['ship_state'] = $data['shipping_zone']; 
				$request['ship_zip_code'] = $data['shipping_postcode']; 
				$request['ship_country'] = $data['shipping_iso_code_2']; 
			} 

			$request['email_hash'] = $this->hashIt($data['email']); 
			$request['amount'] = $this->currency->format($data['total'], $data['currency_code'], $data['currency_value'], false); 
			$request['quantity'] = 1; 
			$request['currency'] =$data['currency_code']; 
			$request['user_order_id'] = $data['order_id']; 
			$request['format'] = 'json'; 

			// Trying to connect 3 times to FraudLabs PRo Web Service
			for ($i = 0; $i < 3; $i++) {
				$response = $this->getRequest('https://api.fraudlabspro.com/v1/order/screen?' . http_build_query($request));
				
				if (is_null($json = json_decode($response)) === false) {
					$this->db->query("REPLACE INTO " . DB_PREFIX . "fraudlabspro SET order_id = '" . (int)$data['order_id'] . "', 
						is_country_match = '" . $this->db->escape($json->is_country_match) . "', 
						is_high_risk_country = '" . $this->db->escape($json->is_high_risk_country) . "', 
						distance_in_km = '" . $this->db->escape($json->distance_in_km) . "', 
						distance_in_mile = '" . $this->db->escape($json->distance_in_mile) . "', 
						ip_country = '" . $this->db->escape($json->ip_country) . "', 
						ip_region = '" . $this->db->escape($json->ip_region) . "', 
						ip_city = '" . $this->db->escape($json->ip_city) . "', 
						ip_continent = '" . $this->db->escape($json->ip_continent) . "', 
						ip_latitude = '" . $this->db->escape($json->ip_latitude) . "', 
						ip_longitude = '" . $this->db->escape($json->ip_longitude) . "', 
						ip_timezone = '" . $this->db->escape($json->ip_timezone) . "', 
						ip_elevation = '" . $this->db->escape($json->ip_elevation) . "', 
						ip_domain = '" . $this->db->escape($json->ip_domain) . "', 
						ip_mobile_mnc = '" . $this->db->escape($json->ip_mobile_mnc) . "', 
						ip_mobile_mcc = '" . $this->db->escape($json->ip_mobile_mcc) . "', 
						ip_mobile_brand = '" . $this->db->escape($json->ip_mobile_brand) . "', 
						ip_netspeed = '" . $this->db->escape($json->ip_netspeed) . "', 
						ip_isp_name = '" . $this->db->escape($json->ip_isp_name) . "', 
						ip_usage_type = '" . $this->db->escape($json->ip_usage_type) . "', 
						is_free_email = '" . $this->db->escape($json->is_free_email) . "', 
						is_new_domain_name = '" . $this->db->escape($json->is_new_domain_name) . "', 
						is_proxy_ip_address = '" . $this->db->escape($json->is_proxy_ip_address) . "', 
						is_bin_found = '" . $this->db->escape($json->is_bin_found) . "', 
						is_bin_country_match = '" . $this->db->escape($json->is_bin_country_match) . "', 
						is_bin_name_match = '" . $this->db->escape($json->is_bin_name_match) . "', 
						is_bin_phone_match = '" . $this->db->escape($json->is_bin_phone_match) . "', 
						is_bin_prepaid = '" . $this->db->escape($json->is_bin_prepaid) . "', 
						is_address_ship_forward = '" . $this->db->escape($json->is_address_ship_forward) . "', 
						is_bill_ship_city_match = '" . $this->db->escape($json->is_bill_ship_city_match) . "', 
						is_bill_ship_state_match = '" . $this->db->escape($json->is_bill_ship_state_match) . "', 
						is_bill_ship_country_match = '" . $this->db->escape($json->is_bill_ship_country_match) . "', 
						is_bill_ship_postal_match = '" . $this->db->escape($json->is_bill_ship_postal_match) . "', 
						is_ip_blacklist = '" . $this->db->escape($json->is_ip_blacklist) . "', 
						is_email_blacklist = '" . $this->db->escape($json->is_email_blacklist) . "', 
						is_credit_card_blacklist = '" . $this->db->escape($json->is_credit_card_blacklist) . "',  
						is_device_blacklist = '" . $this->db->escape($json->is_device_blacklist) . "', 
						is_user_blacklist = '" . $this->db->escape($json->is_user_blacklist) . "', 
						fraudlabspro_score = '" . $this->db->escape($json->fraudlabspro_score) . "', 
						fraudlabspro_distribution = '" . $this->db->escape($json->fraudlabspro_distribution) . "', 
						fraudlabspro_status = '" . $this->db->escape($json->fraudlabspro_status) . "', 
						fraudlabspro_id = '" . $this->db->escape($json->fraudlabspro_id) . "', 
						fraudlabspro_error = '" . $this->db->escape($json->fraudlabspro_error_code) . "', 
						fraudlabspro_message = '" . $this->db->escape($json->fraudlabspro_message) . "', 
						fraudlabspro_credits = '" .  $this->db->escape($json->fraudlabspro_credits) . "', 
						api_key = '" .  $this->config->get('fraudlabspro_key') . "', 
						ip_address = '" .  $data['ip'] . "'
					");

					$risk_score = (int)$json->fraudlabspro_score; 

					break; 
				}

				// Do not perform any action if error found
				if ($json->fraudlabspro_error_code) {
					return;
				}
			}
		}

		if ($risk_score > $this->config->get('fraudlabspro_score')) {
			$fraud_status_id = $this->config->get('fraudlabspro_order_status_id');
		} elseif ($json->fraudlabspro_status == 'REVIEW') {
			$fraud_status_id = $this->config->get('fraudlabspro_review_status_id');
		} elseif ($json->fraudlabspro_status == 'APPROVE') {
			$fraud_status_id = $this->config->get('fraudlabspro_approve_status_id');
		} elseif ($json->fraudlabspro_status == 'REJECT') {
			$fraud_status_id = $this->config->get('fraudlabspro_reject_status_id');
		} else {
			$fraud_status_id = 0;
		}

		return $fraud_status_id;
	}
		
	public function getFraud($order_id) {
		$query = $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "fraudlabspro WHERE order_id = '" . (int)$order_id . "'");
	
		return $query->row;
	}
}
?>