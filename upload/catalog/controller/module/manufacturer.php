<?php
class ControllerModuleManufacturer extends Controller {
	private $_name = 'manufacturer';

	protected function index($setting) {
		static $module = 0;

		$this->language->load('module/manufacturer');

		$this->data['heading_title'] = $this->language->get('heading_title');

		// Module
		$this->data['theme'] = $this->config->get($this->_name . '_theme');
		$this->data['title'] = $this->config->get($this->_name . '_title' . $this->config->get('config_language_id'));

		if (!$this->data['title']) {
			$this->data['title'] = $this->data['heading_title'];
		}

		$header_color = $this->config->get($this->_name . '_header_color');
		$header_shape = $this->config->get($this->_name . '_header_shape');
		$content_color = $this->config->get($this->_name . '_content_color');
		$content_shape = $this->config->get($this->_name . '_content_shape');

		$this->data['header_color'] = ($header_color) ? $header_color : 'white';
		$this->data['header_shape'] = ($header_shape) ? $header_shape : 'rounded-3';
		$this->data['content_color'] = ($content_color) ? $content_color : 'white';
		$this->data['content_shape'] = ($content_shape) ? $content_shape : 'rounded-3';

		$this->data['text_select'] = $this->language->get('text_select');

		if (isset($this->request->get['manufacturer_id'])) {
			$this->data['manufacturer_id'] = $this->request->get['manufacturer_id'];
		} else {
			$this->data['manufacturer_id'] = 0;
		}
        
		$this->load->model('catalog/manufacturer');

		$this->data['manufacturers'] = array();

		$results = $this->model_catalog_manufacturer->getManufacturers(0);

		foreach ($results as $result) {
			$this->data['manufacturers'][] = array(
				'manufacturer_id' => $result['manufacturer_id'],
				'name'            => $result['name'],
				'status'          => $result['status'],
				'href'            => $this->url->link('product/manufacturer/info', 'manufacturer_id=' . $result['manufacturer_id'])
			);
		}

		$this->data['module'] = $module++;

		// Template
		$this->data['template'] = $this->config->get('config_template');

		if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/module/' . $this->_name . '.tpl')) {
			$this->template = $this->config->get('config_template') . '/template/module/' . $this->_name . '.tpl';
		} else {
			$this->template = 'default/template/module/' . $this->_name . '.tpl';
		}

		$this->render();
	}
}
?>