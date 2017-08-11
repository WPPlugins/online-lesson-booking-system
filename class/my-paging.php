<?php
/** 
 *	ページング: Paging
 */

class olbPaging {
	public $limit = null;
	public $offset = null;
	public $recordmax = null;
	public $currentpage = null;
	public $pagemax = null;
	public $currentdate = null;

	public function getCurrentPage(){
		$this->offset = 0;
		$this->currentpage = 1;
		$this->currentdate = date('Y-m-d', current_time('timestamp'));

		if(isset($_SERVER['QUERY_STRING'])) {

			parse_str($_SERVER['QUERY_STRING'], $qs);
			// 現在のページ
			if(isset($qs['tp'])){
				$page = intval($qs['tp']);
				if($page>0 && $page<= $this->pagemax) {
					$this->currentpage = $page;
					$this->offset = ($this->currentpage - 1) * $this->limit;
				}
			}
			// 日付指定
			if(isset($qs['date'])){
				if(preg_match('/^([2-9][0-9]{3})-(0[1-9]{1}|1[0-2]{1})-(0[1-9]{1}|[1-2]{1}[0-9]{1}|3[0-1]{1})$/', $qs['date'])) {
					$this->currentdate = $qs['date'];
				}
			}
		}
	}

	// Next Page
	public function getNextPageLink($inc, $linktext, $query_string, $baseurl = '', $out = false){
		/** 
		 *	$inc 			:増減値
		 *	$linktext		:リンク文字列
		 *	$query_string 	:URLのQUERY_STRING
		 *	$baseurl		:リンク先URL
		 *	$out 			:出力
		 */

		$nextpage = 0;
		// prev
		if($inc<0){
			if($this->currentpage > 1) {
				$nextpage = $this->currentpage + $inc;
				$linktext .= sprintf('(%d/%d)', $nextpage, $this->pagemax);
			//	$offset = $offset - $limit;
			}
		}
		// next
		else {
			if($this->currentpage < $this->pagemax){
				$nextpage = $this->currentpage + $inc;
				$linktext = sprintf('(%d/%d)', $nextpage, $this->pagemax).$linktext;
			//	$offset = $offset + $limit;
			}
		}

		$html = '';
		if($nextpage) {
			parse_str($query_string, $next);
			$next['tp'] = $nextpage;
			$nextq = http_build_query($next);
			ob_start();
			printf('<a href="%s?%s">%s</a>', $baseurl, $nextq, $linktext); 
			$html .= ob_get_contents();
			ob_end_clean();
		}
		else {
			$html .= '&nbsp;';
		}
		if($out) {
			echo $html;
		}
		else {
			return $html;
		}
	}

	// Prev Page
	public function getPrevPageLink($inc, $linktext, $query_string, $baseurl = '', $out = false){
		if($out) {
			self::getNextPageLink($inc, $linktext, $query_string, $baseurl, $out);
		}
		else {
			return self::getNextPageLink($inc, $linktext, $query_string, $baseurl, $out);
		}
	}

	// Next Date
	public static function getNextDateLink($currentdate, $inc, $linktext, $query_string, $baseurl = '', $out = false){
		/** 
		 *	$currentdate	:基準日
		 *	$inc 			:増減値
		 *	$linktext		:リンク文字列
		 *	$query_string 	:URLのQUERY_STRING
		 *	$baseurl		:リンク先URL
		 *	$out 			:出力
		 */

		// prev / next
		$nextdate = strtotime($currentdate) + $inc*60*60*24;

		$html = '';
		parse_str($query_string, $next);
		$next['date'] = date('Y-m-d', $nextdate);
		$nextq = http_build_query($next);
		ob_start();
		printf('<a href="%s?%s">%s</a>', $baseurl, $nextq, $linktext); 
		$html .= ob_get_contents();
		ob_end_clean();
		if($out) {
			echo $html;
		}
		else {
			return $html;
		}
	}

	// Prev Date
	public static function getPrevDateLink($currentdate, $inc, $linktext, $query_string, $baseurl = '', $out = false){
		if($out) {
			self::getNextDateLink($currentdate, $inc, $linktext, $query_string, $baseurl, $out);
		}
		else {
			return self::getNextDateLink($currentdate, $inc, $linktext, $query_string, $baseurl, $out);
		}
	}

	// Page Navi
	public static function page_navi( $records ) {
		$format = <<<EOD
<div id="list_pagenavi" class="list_pagenavi">
<div id="prev_page" class="prev_page">%PREV_PAGE%</div>
<div id="next_page" class="next_page">%NEXT_PAGE%</div>
</div>
EOD;
		$search = array(
				'%PREV_PAGE%',
				'%NEXT_PAGE%',
			);
		$text_prev = __('&laquo; PREV', OLBsystem::TEXTDOMAIN);
		$text_next = __('NEXT &raquo;', OLBsystem::TEXTDOMAIN);
		$replace = array(
				$records->getPrevPageLink(-1, $text_prev, $_SERVER['QUERY_STRING']),
				$records->getNextPageLink( 1, $text_next, $_SERVER['QUERY_STRING']),
			);
		return str_replace($search, $replace, $format);
	}

}

?>
