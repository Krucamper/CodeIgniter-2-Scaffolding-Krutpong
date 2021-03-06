<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * CodeIgniter
 *
 * An open source application development framework for PHP 4.3.2 or newer
 *
 * @package		CodeIgniter
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2008 - 2010, EllisLab, Inc.
 * @license		http://codeigniter.com/user_guide/license.html
 * @link		http://codeigniter.com
 * @since		Version 1.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * Scaffolding Class
 *
 * Provides the Scaffolding framework
 *
 * @package		CodeIgniter
 * @subpackage	Scaffolding
 * @author		ExpressionEngine Dev Team
 * @link		http://codeigniter.com/user_guide/general/scaffolding.html
 */
class Scaffolding extends CI_Controller {

	var $CI;
	var $current_table;
	var $base_url = '';
	var $table_url;
	var $tables_list;
    var $search_url;
    var $search_field;
    var $search_keyword;

	function __construct()
	{



		parent::__construct();
		$this->load->helper('url');

		$this->CI =& get_instance();
		
		$this->CI->load->database("", FALSE, TRUE);			
		$this->CI->load->library('pagination');
		
		// Turn off caching
		$this->CI->db->cache_off();

		$this->table_url = ( isset( $_GET['table'] ) ) ? "?table={$_GET['table']}" : '';
        $this->search_field = ( !empty( $_REQUEST['field'])) ? $_REQUEST['field'] : '';
        $this->search_keyword = ( !empty( $_REQUEST['keyword'])) ? $_REQUEST['keyword'] : '';
        $this->search_field = ( !empty( $_GET['field']) AND empty($this->search_field) ) ? $_GET['field'] : $this->search_field;
        $this->search_keyword = ( !empty( $_GET['keyword']) AND empty($this->search_keyword) ) ? $_GET['keyword'] : $this->search_keyword;
        $this->search_url = ( !empty($this->table_url) AND !empty( $this->search_field) AND !empty($this->search_keyword) ) ? $this->table_url."&field=".$this->search_field.'&'."keyword=".$this->search_keyword : '';
		$this->tables_list = $this->db->list_tables();	

		/**
		* If scaffolding disable is set to true, redirect to default controller
		*/
		if( $this->config->item('scaffolding_disable') )
		{
			redirect($this->router->default_controller);
			exit;
		}
		/**
		 * Set the current table name
		 */		
		$this->current_table = ( isset($_GET['table']) )  ? $_GET['table'] : ( ($this->config->item('scaffolding_table')) ? $this->config->item('scaffolding_table') : $this->tables_list[0] );		
		

		// Set the base URL
		$this->base_url = $this->CI->config->site_url().'/'.$this->CI->uri->segment(1).$this->CI->uri->slash_segment(2, 'both');
		$this->base_uri = $this->CI->uri->segment(1).$this->CI->uri->slash_segment(2, 'leading');

		// Set a few globals
		$data = array(
						'image_url'	=> $this->CI->config->system_url().'scaffolding/images/',
						'base_uri'  => $this->base_uri,
						'base_url'	=> $this->base_url,
						'title'		=> $this->current_table,
						'table_url' => $this->table_url,
						'tables' 	=> $this->tables_list,
                        'search_keyword'   => $this->search_keyword,
                        'search_field'     => $this->search_field
					);
		
		$this->CI->load->vars($data);			

		// Load the language file
		$this->lang->load('scaffolding');
				
		//  Load the helper files we plan to use
		$this->CI->load->helper(array('url', 'form'));
		
				
		log_message('debug', 'Scaffolding Class Initialized');
	}
	
	function index()
	{
		$this->view();
	}

	// --------------------------------------------------------------------
	
	/**
	 * "Add" Page
	 *
	 * Shows a form representing the currently selected DB
	 * so that data can be inserted
	 *
	 * @access	public
	 * @return	string	the HTML "add" page
	 */
	function add()
	{
        $fields = $this->db->field_data($this->current_table);

        foreach($fields as $key => $value)
        {
            if($value->type == 'enum') $fields[$key]->enum_value = $this->get_enum_values($this->current_table,$value->name);
        }


		$data = array(
						'title'	=>  $this->lang->line('scaff_add'),						
 						'fields' => $fields,
						'table_url' => $this->table_url		
					);


		$this->load->view('scaffolding/header');
		$this->load->view('scaffolding/add', $data);
		$this->load->view('scaffolding/footer', $data);
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Insert the data
	 *
	 * @access	public
	 * @return	void	redirects to the view page
	 */
	function insert()
	{		
		if ($this->db->insert($this->current_table, $_POST) === FALSE)
		{
			$this->add();
		}
		else
		{
			redirect('scaffolding'.$this->table_url);
		}
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * "View" Page
	 *
	 * Shows a table containing the data in the currently
	 * selected DB
	 *
	 * @access	public
	 * @return	string	the HTML "view" page
	 */
	function view()
	{
		// Fetch the total number of DB rows

        // with search LIKE % ?? %

        if(!empty($this->search_field) AND !empty($this->search_keyword))
            $this->db->like($this->search_field , $this->search_keyword);
        $this->db->from($this->current_table);
        $total_rows = $this->db->count_all_results();

		// Set the query limit/offset
		$per_page = 100;
		$offset = !empty($_GET['per_page']) ? $_GET['per_page'] : 0;

		// Run the query

        // with search LIKE % ?? %
        if(!empty($this->search_field) AND !empty($this->search_keyword))
            $this->db->like($this->search_field , $this->search_keyword);

		$query = $this->db->get($this->current_table, $per_page, $offset);

		// Now let's get the field names				
		$fields = $this->db->list_fields($this->current_table);
		
		// We assume that the column in the first position is the primary field.
		$primary = current($fields);

		// Pagination!
		$this->pagination->initialize(
							array(
									'base_url'		 => site_url('scaffolding/index').(!empty($this->search_url) ? $this->search_url : $this->table_url ),
									'total_rows'	 => $total_rows,
									'per_page'		 => $per_page,
									'full_tag_open'	 => '<p>',
									'full_tag_close' => '</p>',
                                    'page_query_string' => true
									)
								);	

		$data = array(
						'title'	=>  $this->lang->line('scaff_view'),
						'query'		=> $query,
						'fields'	=> $fields,
						'primary'	=> $primary,
						'paginate'	=> $this->pagination->create_links(),
						'table_url' => $this->table_url		
					);

		$this->load->view('scaffolding/header',$data);
		$this->load->view('scaffolding/view', $data);
		$this->load->view('scaffolding/footer', $data);
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * "Edit" Page
	 *
	 * Shows a form representing the currently selected DB
	 * so that data can be edited
	 *
	 * @access	public
	 * @return	string	the HTML "edit" page
	 */
	function edit()
	{
		if (FALSE === ($id = $this->CI->uri->segment(3)))
		{
			return $this->view();
		}

		// Fetch the primary field name
		$primary = $this->db->primary($this->current_table);		

		// Run the query
		$query = $this->db->get_where($this->current_table, array($primary => $id));

        $fields = $this->db->field_data($this->current_table);

        foreach($fields as $key => $value)
        {
            if($value->type == 'enum') $fields[$key]->enum_value = $this->get_enum_values($this->current_table,$value->name);
        }


        $data = array(
						'title'	=>  $this->lang->line('scaff_edit'),
						'fields'	=> $fields,
						'query'		=> $query->row(),						
						'table_url' => $this->table_url		
					);

		$this->load->view('scaffolding/header');
		$this->load->view('scaffolding/edit', $data);
		$this->load->view('scaffolding/footer', $data);
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Update
	 *
	 * @access	public
	 * @return	void	redirects to the view page
	 */
	function update()
	{	
		// Fetch the primary key
		$primary = $this->db->primary($this->current_table);				

		// Now do the query
		$this->db->update($this->current_table, $_POST, array($primary => $this->CI->uri->segment(3)));
		
		redirect('scaffolding'.$this->table_url);
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Delete Confirmation
	 *
	 * @access	public
	 * @return	string	the HTML "delete confirm" page
	 */
	function delete()
	{

		$data = array(
						'title'		=> $this->lang->line('scaff_delete'),
						'message'	=> $this->lang->line('scaff_del_confirm').' '.$this->uri->segment(3),
						'no'		=> anchor('scaffolding'.$this->table_url, $this->lang->line('scaff_no')),
						'yes'		=> anchor('scaffolding/do_delete/'. $this->uri->segment(3).$this->table_url, $this->lang->line('scaff_yes')),
						'table_url' => $this->table_url		
					);
	
		$this->load->view('scaffolding/header');
		$this->load->view('scaffolding/delete', $data);
		$this->load->view('scaffolding/footer', $data);
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Delete
	 *
	 * @access	public
	 * @return	void	redirects to the view page
	 */
	function do_delete()
	{		
		// Fetch the primary key
		$primary = $this->db->primary($this->current_table);				

		// Now do the query
		$this->db->where($primary, $this->CI->uri->segment(3));
		$this->db->delete($this->current_table);

		redirect('scaffolding'.$this->table_url);
		exit;
	}


    function get_enum_values( $table, $field )
    {
        $type = $this->db->query( "SHOW COLUMNS FROM {$table} WHERE Field = '{$field}'" )->row( 0 )->Type;
        preg_match("/^enum\(\'(.*)\'\)$/", $type, $matches);
        $enum = explode("','", $matches[1]);
        return $enum;
    }

}

/* End of file scaffolding.php */
/* Location: ./applicatin/controller/scaffolding.php */