<?php
class ControllerExtensionModuleSlideshow extends Controller {
	public function index($setting) {
		static $module = 0;		

		$this->load->model('design/banner');
		$this->load->model('tool/image');

		$this->document->addStyle('catalog/view/javascript/jquery/swiper/css/swiper.min.css');
		$this->document->addStyle('catalog/view/javascript/jquery/swiper/css/opencart.css');
		$this->document->addScript('catalog/view/javascript/jquery/swiper/js/swiper.jquery.min.js');
		
		$data['banners'] = array();

		$results = $this->model_design_banner->getBanner($setting['banner_id']);

		foreach ($results as $result) {

            $banner = array();

			if (is_file(DIR_IMAGE . $result['image'])) {
                $banner = array(
                    'title' => $result['title'],
                    'link'  => $result['link'],
                    'image' => $this->model_tool_image->resize($result['image'], $setting['width'], $setting['height'])
                );
			}

            $mobile_image = preg_replace('/([\w\-\_\/])(\.)([\w]+)/ui', '$1-mob.$3', $result['image']);

            if (is_file(DIR_IMAGE . $mobile_image)) {
                $banner['mobile_image'] = $this->model_tool_image->resize($mobile_image, '767', $setting['height']);
            }

            if (!empty($banner)) {
                $data['banners'][] = $banner;
            }
		}

		$data['module'] = $module++;

		return $this->load->view('extension/module/slideshow', $data);
	}
}