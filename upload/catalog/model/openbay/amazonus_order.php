<?php
class ModelOpenbayAmazonusOrder extends Model {

	public function acknowledgeOrder($orderId) {
		$amazonusOrderId = $this->getAmazonusOrderId($orderId);

		$requestXml = "<Request>
  <AmazonOrderId>$amazonusOrderId</AmazonOrderId>
  <MerchantOrderId>$orderId</MerchantOrderId>
</Request>";

		$this->openbay->amazonus->callNoResponse('order/acknowledge', $requestXml, false);
	}

	public function getProductId($sku) {
		$row = $this->db->query("SELECT `product_id` FROM `" . DB_PREFIX . "amazonus_product_link` WHERE `amazonus_sku` = '" . $this->db->escape($sku) . "'")->row;

		if (isset($row['product_id']) && !empty($row['product_id'])) {
			return $row['product_id'];
		}

		return 0;
	}

	public function getProductVar($sku) {
		$row = $this->db->query("SELECT `var` FROM `" . DB_PREFIX . "amazonus_product_link` WHERE `amazonus_sku` = '" . $this->db->escape($sku) . "'")->row;

		if (isset($row['var'])) {
			return $row['var'];
		}

		return '';
	}

    public function decreaseProductQuantity($productId, $delta, $var = '') {
        if ($productId == 0) {
            return;
        }

        if ($var == '') {
            $this->db->query("UPDATE `" . DB_PREFIX . "product` SET `quantity` = GREATEST(`quantity` - '" . (int)$delta . "', 0) WHERE `product_id` = '" . (int)$productId . "' AND `subtract` = '1'");
        } else {
            $this->db->query("UPDATE `" . DB_PREFIX . "product_option_relation` SET `stock` = GREATEST(`stock` - '" . (int)$delta . "', 0) WHERE `product_id` = '" . (int)$productId . "' AND `var` = '" . $this->db->escape($var) . "' AND `subtract` = '1'");
        }
    }

	public function getMappedStatus($amazonusStatus) {
		$amazonusStatus = trim(strtolower($amazonusStatus));

		switch ($amazonusStatus) {
			case 'pending':
				$orderStatus = $this->config->get('openbay_amazonus_order_status_pending');
				break;

			case 'unshipped':
				$orderStatus = $this->config->get('openbay_amazonus_order_status_unshipped');
				break;

			case 'partiallyshipped':
				$orderStatus = $this->config->get('openbay_amazonus_order_status_partially_shipped');
				break;

			case 'shipped':
				$orderStatus = $this->config->get('openbay_amazonus_order_status_shipped');
				break;

			case 'canceled':
				$orderStatus = $this->config->get('openbay_amazonus_order_status_canceled');
				break;

			case 'unfulfillable':
				$orderStatus = $this->config->get('openbay_amazonus_order_status_unfulfillable');
				break;

			default:
				$orderStatus = $this->config->get('config_order_status_id');
				break;
		}

		return $orderStatus;
	}

	public function getCountryName($countryCode) {
		$row = $this->db->query("SELECT `name` FROM `" . DB_PREFIX . "country` WHERE LOWER(`iso_code_2`) = '" . $this->db->escape(strtolower($countryCode)) . "'")->row;

		if (isset($row['name']) && !empty($row['name'])) {
			return $row['name'];
		}

		return '';
	}

	public function getCountryId($countryCode) {
		$row = $this->db->query("SELECT `country_id` FROM `" . DB_PREFIX . "country` WHERE LOWER(`iso_code_2`) = '" . $this->db->escape(strtolower($countryCode)) . "'")->row;

		if (isset($row['country_id']) && !empty($row['country_id'])) {
			return (int)$row['country_id'];
		}

		return 0;
	}

	public function getZoneId($zoneName) {
		$row = $this->db->query("SELECT `zone_id` FROM `" . DB_PREFIX . "zone` WHERE LOWER(`name`) = '" . $this->db->escape(strtolower($zoneName)) . "'")->row;

		if (isset($row['country_id']) && !empty($row['country_id'])) {
			return (int)$row['country_id'];
		}

		return 0;
	}

	public function updateOrderStatus($orderId, $statusId) {
		$this->db->query("INSERT INTO `" . DB_PREFIX . "order_history` (`order_id`, `order_status_id`, `notify`, `comment`, `date_added`) VALUES (" . (int)$orderId . ", " . (int)$statusId . ", 0, '', NOW())");

		$this->db->query("UPDATE `" . DB_PREFIX . "order` SET `order_status_id` = " . (int)$statusId . " WHERE `order_id` = " . (int)$orderId);
	}

	public function addAmazonusOrder($orderId, $amazonusOrderId) {
		$this->db->query("INSERT INTO `" . DB_PREFIX . "amazonus_order` (`order_id`, `amazonus_order_id`) VALUES (" . (int)$orderId . ", '" . $this->db->escape($amazonusOrderId) . "')");
	}

	/* $data = array(PRODUCT_SKU => ORDER_ITEM_ID) */
	public function addAmazonusOrderProducts($orderId, $data) {
		foreach ($data as $sku => $orderItemId) {
			$row = $this->db->query("SELECT `order_product_id` FROM `" . DB_PREFIX . "order_product` WHERE `model` = '" . $this->db->escape($sku) . "' AND `order_id` = " . (int)$orderId . " LIMIT 1")->row;

			if (!isset($row['order_product_id']) || empty($row['order_product_id'])) {
				continue;
			}

			$orderProductId = $row['order_product_id'];

			$this->db->query("INSERT INTO `" . DB_PREFIX . "amazonus_order_product` (`order_product_id`, `amazonus_order_item_id`) VALUES (" . (int)$orderProductId . ", '" . $this->db->escape($orderItemId) . "')");
		}
	}

	public function getOrderId($amazonusOrderId) {
		$row = $this->db->query("SELECT `order_id` FROM `" . DB_PREFIX . "amazonus_order` WHERE `amazonus_order_id` = '" . $this->db->escape($amazonusOrderId) . "' LIMIT 1")->row;

		if (isset($row['order_id']) && !empty($row['order_id'])) {
			return $row['order_id'];
		}

		return '';
	}

	public function getOrderStatus($orderId) {
		$row = $this->db->query("SELECT `order_status_id` FROM `" . DB_PREFIX . "order` WHERE `order_id` = " . (int)$orderId)->row;

		if (isset($row['order_status_id']) && !empty($row['order_status_id'])) {
			return $row['order_status_id'];
		}

		return 0;
	}

	public function getAmazonusOrderId($orderId) {
		$row = $this->db->query("SELECT `amazonus_order_id` FROM `" . DB_PREFIX . "amazonus_order` WHERE `order_id` = " . (int)$orderId . " LIMIT 1")->row;

		if (isset($row['amazonus_order_id']) && !empty($row['amazonus_order_id'])) {
			return $row['amazonus_order_id'];
		}

		return null;
	}

	public function getProductOptionsByVar($productVar) {
		$options = array();

		$optionValueIds = explode(':', $productVar);

		foreach ($optionValueIds as $optionValueId) {
			$optionDetailsRow = $this->db->query("SELECT pov.product_option_id, pov.product_option_value_id, od.name, ovd.name as value, opt.type
			FROM `" . DB_PREFIX . "product_option_value` pov,
				 `" . DB_PREFIX . "product_option` po,
				 `" . DB_PREFIX . "option` opt,
				 `" . DB_PREFIX . "option_value_description` ovd,
				 `" . DB_PREFIX . "option_description` od
			WHERE pov.product_option_value_id = '" . (int)$optionValueId . "' AND
				po.product_option_id = pov.product_option_id AND
				opt.option_id = pov.option_id AND
				ovd.option_value_id = pov.option_value_id AND ovd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND
				od.option_id = pov.option_id AND od.language_id = '" . (int)$this->config->get('config_language_id') . "'
			")->row;

			if (!empty($optionDetailsRow)) {
				$options[] = array(
					'product_option_id'       => (int)$optionDetailsRow['product_option_id'],
					'product_option_value_id' => (int)$optionDetailsRow['product_option_value_id'],
					'name'                    => $optionDetailsRow['name'],
					'value'                   => $optionDetailsRow['value'],
					'type'                    => $optionDetailsRow['type']
				);
			}
		}

		return $options;
	}
}
