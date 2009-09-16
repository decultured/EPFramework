<?php

function is_multi_array($array = array()) {
	if(is_array($array) == false) { return false; }
	
	return is_array(array_shift($array));
}

class GoogleChart {
	public $width;
	public $height;
	protected $chart_type;
	
	protected $dataset;
	protected $dataset_styles;
	protected $dataset_labels;
	
	protected $axis_labels;
	
	protected $chart_colors;
	
	function __construct() {
		$this->width = 300;
		$this->height = 100;
		$this->chart_type = 'lc';
		
		$this->chart_colors = array('B50000', '11C316', '1188C3', 'CF8304', 'CACF04', '0E419F', '6C0E9F', 'AA0D03', '0BA2F7', 'FFD903');
	}
	
	function addDataset($dataset = array(), $label = '', $style = '') {
		$this->dataset[] = $dataset;
		$this->dataset_labels[] = $label;
		$this->dataset_styles[] = $style;
	}
	
	function encodeData($dataset = array(), $type = 't') {
		if(is_array($dataset) == false || count($dataset) == 0) {
			return '';
		}
		
		switch(strtolower($type)) {
			case 'e':
				// extended encoding, supports a data resolution of 0-4096
				$encoding = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-.';
				
				$scaled = array();
				foreach($this->dataset as $set) {
					$set = $this->scaleArray($set, 0, 4096, 0);
					for($i = 0; $i < count($set); $i++) {
						if($set[$i] == null) {
							$set[$i] = '__';
						} else {
							// convert the value to a character from our encoding list
							$set[$i] = substr($encoding, floor($set[$i] / 64), 1) . substr($encoding, $set[$i] % 64, 1);
						}
					}
					
					$scaled[] = implode('', $set);
				}
				// extended encoding, done!
				return 'e:' . implode(',', $scaled);
			break;
			case 's':
				// simple encoding, supports a data resolution of 0-61
				$encoding = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
				$scaled = array();
				foreach($this->dataset as $set) {
					$set = $this->scaleArray($set, 0, 61, 0);
					for($i = 0; $i < count($set); $i++) {
						if($set[$i] == null) {
							$set[$i] = '_';
						} else {
							// convert the value to a character from our encoding list
							$set[$i] = substr($encoding, $set[$i], 1);
						}
					}
					
					$scaled[] = implode('', $set);
				}
				// simple encoding, done
				return 's:' . implode(',', $scaled);
			break;
			case 't':
				$scaled = array();
				foreach($this->dataset as $set) {
					// convert our set into a scaled array, 0 to 100
					$scaled[] = implode(',', $this->scaleArray($set, 0, 100.0));
				}
				// text encoding, done
				return 't:' . implode('|', $scaled);
			break;
		}
	}
	
	function scaleArray($data = array(), $y_min = 0, $y_max = 100.0, $percision = 1) {
		if(count($data) == 0) {
			return array();
		}
		
		$scaled = array();
		$max = max($data);
		
		if($max == 0) {
			return $data;
		}
		
		$resolution = $y_max - $y_min;
		
		foreach($data as $value) {
			$scaled[] = round(($value / $max) * $resolution, $percision);
		}
		
		return $scaled;
	}
	
	function addAxisLabel($axis = 'x', $labels = array(), $style = '') {
		$this->axis_labels[] = array('axis' => $axis, 'labels' => $labels, 'style' => $style);
	}
	
	function encodeAxisLabels() {
		$axis = array();
		$labels = array();
		$styles = array();
		
		$axis_index = 0;
		foreach($this->axis_labels as $row) {
			if(is_array($row['labels']) == false) {
				continue;
			}
			
			// sort($row['labels']);
			$max = array_pop($row['labels']);
			
			$axis[] = $row['axis'];
			$labels[] = "{$axis_index}:|" . (count($row['labels']) > 0 ? implode('|', $row['labels']) : '') . '|' . $max;
			$styles[] = $row['style'];
			
			$axis_index++;
		}
		// dump($labels);
		if(count($labels) > 0) {
			return 'chxt=' . implode(',', $axis) . '&chxl=' . implode('|', $labels);
		}
	}
	
	function getUrl() {
		$url = "http://chart.apis.google.com/chart?cht={$this->chart_type}&chd={$this->encodeData($this->dataset, 'e')}&chs={$this->width}x{$this->height}&{$this->encodeAxisLabels()}";
		
		// if(count($this->dataset) > 1) {
			// add more colors to the chart
			$chart_colors = array_slice($this->chart_colors, 0, count($this->dataset));
			$url .= "&chco=". implode(",", $chart_colors);
		// }
		
		if(count($this->dataset_styles) > 0) 
			$url .= "&chls=" . implode("|", $this->dataset_styles);
		// die($url);
		return $url;
	}
}

class GoogleChartMap extends GoogleChart {
	private $geographic_area;
	
	function __construct() {
		parent::__construct();
		
		$this->chart_type = 't';
		$this->geographic_area = 'world';
	}
	
	function encodeData($dataset = array(), $type = 'e') {
		if(is_array($dataset) == false || count($dataset) == 0) {
			return '';
		}
		
		switch(strtolower($type)) {
			case 'e':
				// extended encoding, supports a data resolution of 0-4096
				$encoding = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-.';
				
				$codes = array();
				$values = array();
				foreach($this->dataset as $row) {
					$codes[] = $row['region'];
					$values[] = $row['intensity'];
				}
				$values = $this->scaleArray($values, 0, 4096, 0);
				for($i = 0; $i < count($set); $i++) {
					if($values[$i] == null) {
						$values[$i] = '__';
					} else {
						// convert the value to a character from our encoding list
						$values[$i] = substr($encoding, floor($values[$i] / 64), 1) . substr($encoding, $values[$i] % 64, 1);
					}
				}

				// extended encoding, done!
				return array('codes' => $codes, 'values' => 'e:' . implode(',', $values));
			break;
			case 's':
				// simple encoding, supports a data resolution of 0-61
				$encoding = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
				$scaled = array();
				foreach($this->dataset as $set) {
					$set = $this->scaleArray($set, 0, 61, 0);
					for($i = 0; $i < count($set); $i++) {
						if($set[$i] == null) {
							$set[$i] = '_';
						} else {
							// convert the value to a character from our encoding list
							$set[$i] = substr($encoding, $set[$i], 1);
						}
					}
					
					$scaled[] = implode('', $set);
				}
				// simple encoding, done
				return 's:' . implode(',', $scaled);
			break;
			case 't':
				$scaled = array();
				foreach($this->dataset as $set) {
					// convert our set into a scaled array, 0 to 100
					$scaled[] = implode(',', $this->scaleArray($set, 0, 100.0));
				}
				// text encoding, done
				return 't:' . implode('|', $scaled);
			break;
		}
	}
	
	function getUrl() {
		$chart_colors = array(
			'f5f5f5',
			'edf0d4',
			'6c9642',
			'13390a',
		);
		
		$encoded = $this->encodeData($this->dataset, 'e');
		
		return "http://chart.apis.google.com/chart?chco=". implode(',', $chart_colors) ."&chd=s:fSGBDQBQBBAGABCBDAKLCDGFCLBBEBBEPASDKJBDD9BHHEAACAC&chf=bg,s,eaf7fe&chtm=usa&chld=NYPATNWVNVNJNHVAHIVTNMNCNDNELASDDCDEFLWAKSWIORKYMEOHIAIDCTWYUTINILAKTXCOMDMAALMOMNCAOKMIGAAZMTMSSCRIAR&chs=440x220&cht=t";
	}
}

?>