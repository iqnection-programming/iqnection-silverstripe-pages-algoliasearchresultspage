<?
	require_once(dirname(__FILE__)."/../algoliasearch/algoliasearch.php");

	class AlgoliaSearchResultsPage extends Page
	{
		private $client = false;
		private $index = false;
		private $admin_client = false;
		private $admin_index = false;
		
		private static $db = array(
			'AlgoliaApplicationID' => 'Varchar(32)',
			'AlgoliaAPIKey' => 'Varchar(64)',
			'AlgoliaAdminAPIKey' => 'Varchar(64)',
			'AlgoliaPrimaryIndex' => 'Varchar(64)',
			'AlgoliaAdditionalIndecies' => 'Text',
			'AlgoliaCommunityVersion' => 'Boolean',
		);	
		
		private static $defaults = array(
			'ShowInMenus' => false,
			'ShowInSearch' => false,
			'AlgoliaCommunityVersion' => true,
		);
		
		public function GetClient()
		{
			if ($this->client === false)
			{
				$this->client = new AlgoliaSearch\Client($this->AlgoliaApplicationID, $this->AlgoliaAPIKey);
				$this->index = $this->client->initIndex($this->AlgoliaPrimaryIndex);
			}
			return $this->client;
		}
		
		public function GetAdminClient()
		{
			if ($this->admin_client === false)
			{
				$this->admin_client = new AlgoliaSearch\Client($this->AlgoliaApplicationID, $this->AlgoliaAdminAPIKey);
				$this->admin_index = $this->admin_client->initIndex($this->AlgoliaPrimaryIndex);
			}
			return $this->admin_client;
		}
		
		public function GetIndex()
		{
			return $this->index;
		}
				
		public function GetAdminIndex()
		{
			return $this->admin_index;
		}
				
		public function getCMSFields()
		{
			$fields = parent::getCMSFields();
			
			$fields->addFieldToTab('Root.AlgoliaSettings', new TextField('AlgoliaApplicationID','Application ID'));
			$fields->addFieldToTab('Root.AlgoliaSettings', new TextField('AlgoliaAPIKey','Search-Only API Key'));
			$fields->addFieldToTab('Root.AlgoliaSettings', new TextField('AlgoliaAdminAPIKey','Admin API Key'));
			$fields->addFieldToTab('Root.AlgoliaSettings', new TextField('AlgoliaPrimaryIndex','Primary Search Index'));
			$fields->addFieldToTab('Root.AlgoliaSettings', new TextAreaField('AlgoliaAdditionalIndecies','Additional Indecies (One per line)'));
			$fields->addFieldToTab('Root.AlgoliaSettings', new CheckboxField('AlgoliaCommunityVersion','Community Version (Must display Algolia logo)'));

			return $fields;
		}		
	}
	
	class AlgoliaSearchResultsPage_Controller extends Page_Controller
	{	
		private $query = "";
		
		private static $allowed_actions = array(
			"results",
			"BuildIndex",
		);
				
		private static function ExtractHtmlContent($object)
		{
			$latin_text = array(
				"lorem ipsum",
				"donec tristique",
				"morbi tincidunt",
				"donec vitae",
				"velit consectetur",
			);
			
			$html_content = array();
			
			$fields = $object->db();
			
			foreach ($fields as $field_name => $field_type)
			{
				$field_type = preg_replace("/\(.*\)$/", "", $field_type);
				$val = false;

				switch (strtolower($field_type))
				{
					case "htmltext":
						$val = trim(strip_tags($object->{$field_name}));
						break;
				}

				if ($val)
				{
					$is_latin = false;
					foreach ($latin_text as $latin)
					{
						if (strpos(strtolower($val), $latin) !== false)
						{
							$is_latin = true;
							break;
						}
					}

					if (!$is_latin) $html_content[] = $val;
				}				
			}
			
			return $html_content;
		}
		
		public function BuildIndex()
		{
			$client = $this->GetAdminClient();
			$index = $this->GetAdminIndex();
			
			$saved_ids = array();
			
			//print "<pre>client: ".print_r($client, true)."</pre>";
			//print "<pre>index: ".print_r($index, true)."</pre>";
			
			foreach (DataObject::get("SiteTree", "ShowInSearch=1") as $page)
			{
				$class_name = $page->ClassName;
				
				if ($class_page = DataObject::get_by_id($class_name, $page->ID))
				{
					$class_fields = $class_page->db();
					$class_has_one = $class_page->hasOne();
					$class_has_many = $class_page->hasMany();
					
					$page_html_content = array();
					
					// Class fields
					
					$page_html_content = array_merge($page_html_content, self::ExtractHtmlContent($class_page));
					
					// Has One
					
					
					
					// Has Many
					
					foreach ($class_has_many as $field_name => $many_class_name)
					{
						foreach ($class_page->{$field_name}() as $child)
						{
							$page_html_content = array_merge($page_html_content, self::ExtractHtmlContent($child));
						}
					}
					
					if (count($page_html_content))
					{
						try {
							$object_content = implode("\n", $page_html_content);
							if (strlen($object_content) > 9000)
							{
								// Max 10kb content size
								$object_content = substr($object_content, 0, 9000);
							}
							
							$index->saveObject([
								'content' => $object_content,
								'post_title' => $class_page->Title,
								'permalink' => $class_page->AbsoluteLink(),
								'objectID' => $class_page->ID
							]);
							$saved_ids[] = $class_page->ID;
						}
						catch (Exception $e) {
							print "<pre>error: ".print_r($e, true)."</pre>";
							mail("mjarossy@iqnection.com", "Error in Algolia Indexing Process", print_r($e, true));
							die();
						}
					}
					
					//print "<pre>$class_name: ".print_r($class_page->Title, true)."</pre>";
					//print "<pre>page_html_content: ".print_r(implode("\n", $page_html_content), true)."</pre>";
					//print "<pre>class_fields: ".print_r($class_fields, true)."</pre>";
					//print "<pre>class_has_one: ".print_r($class_has_one, true)."</pre>";
					//print "<pre>class_has_many: ".print_r($class_has_many, true)."</pre>";
					
					//die();
				}

			}
			
			$all_objects = $index->browse();
			foreach ($all_objects['hits'] as $object)
			{
				if (!in_array($object['objectID'], $saved_ids)) $index->deleteObject($object['objectID']);
			}
						
			die("complete");
		}
		
		public function PageCSS()
		{
			$files = array_merge(
				parent::PageCSS(),
				array(
					ViewableData::ThemeDir().'/css/forms.css'
				)
			);
			return $files;
		}
		
		private static function sortByScore(&$a, &$b)
		{
			return $a['score'] < $b['score'];
		}
		
		public function results(&$request)
		{
			$client = $this->GetClient();
			$index = $this->GetIndex();
			
			$term = $request->getVar("Search");
			$page = intval($request->getVar("page"));
			$num_pages = 0;
			
			if ($term = trim($term))
			{
				$queries = [
					['indexName' => $index->indexName, 'query' => $term, 'getRankingInfo' => true, 'page' => $page, 'hitsPerPage' => 5]
				];
				
				if ($this->AlgoliaAdditionalIndecies)
				{
					foreach (explode("\n", $this->AlgoliaAdditionalIndecies) as $addl_index)
					{
						$addl_index = trim($addl_index);
						$queries[] = ['indexName' => $addl_index, 'query' => $term, 'getRankingInfo' => true, 'page' => $page, 'hitsPerPage' => 5];
					}
				}
				
				$res = $client->multipleQueries($queries);
				$resultSet = new ArrayList();
				//print "<pre>res: ".print_r($res, true)."</pre>";
				//die();

				foreach ($res['results'] as $search_results)
				{
					$num_pages = max($num_pages, $search_results['nbPages']);
					
					foreach ($search_results['hits'] as $result)
					{
						//print "<pre>result: ".print_r($result, true)."</pre>";
						$post = new DataObject();
						$post->Score = $result['_rankingInfo']['userScore'];
						$post->Title = $result['post_title'];
						$post->Link = $result['permalink'];
						$post->Content = $result['content'];
						$post->TitleHighlighted = $result['_highlightResult']['post_title']['value'] ? $result['_highlightResult']['post_title']['value'] : $result['post_title'];
						$post->LinkHighlighted = $result['_highlightResult']['permalink']['value'] ? $result['_highlightResult']['permalink']['value'] : $result['permalink'];
						$post->ContentHighlighted = $result['_highlightResult']['content']['value'] ? $result['_highlightResult']['content']['value'] : $result['content'];

						$resultSet->push($post);
					}
				}
				//print "<pre>$res: ".print_r($res, true)."</pre>";
				
				$resultSet = $resultSet->sort("Score DESC");
				
				$this->query = $term;
				
				return $this->customise(array("Query" => $term, "Pagination" => $this->Pagination($page+1, $num_pages), "Results" => $resultSet))->renderWith(array("AlgoliaSearchResultsPage_results", "Page"));
			}
			
			return $this->redirect($this->Link());
		}
		
		private function SearchPageLink($n, $curr_page=0, $text=false)
		{
			return "<span class='pagenum'>".($curr_page == $n ? "<b>".($text ? $text : intval($n))."</b>" : "<a href='".$this->Link()."results?Search=".rawurlencode($this->query)."&page=".($n-1)."'>".($text ? $text : intval($n))."</a>")."</span>";
		}
		
		private function Pagination($page, $num_pages)
		{
			$page_links = "<div class='page-links'>";
			$page_links .= "<b>Page:</b> ";

			if ($num_pages > 1) $page_links .= $this->SearchPageLink($page - 1, ($page == 1 ? ($page - 1) : 0), "&laquo;");

			if ($num_pages < 6)
			{
				for ($i = 1; $i <= $num_pages; $i++)
					$page_links .= $this->SearchPageLink($i, $page);
			}
			else
			{
				$page_links .= $this->SearchPageLink(1, $page);

				if ($page > 3) $page_links .= "<span class='pagenum'>...</span>";

				if ($page > 2) $page_links .= $this->SearchPageLink($page-1, $page);
				if ($page > 1 && $page < $num_pages) $page_links .= $this->SearchPageLink($page, $page);
				if ($page < ($num_pages - 1)) $page_links .= $this->SearchPageLink($page+1, $page);

				if ($page < ($num_pages - 2)) $page_links .= "<span class='pagenum'>...</span>";

				$page_links .= $this->SearchPageLink($num_pages, $page);
			}

			if ($num_pages > 1) $page_links .= $this->SearchPageLink($page + 1, ($page == $num_pages ? ($page + 1) : 0), "&raquo;");

			$page_links .= "</div>";
			
			return $page_links;
		}
	}
	

	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
