<?php

class ph_slug_category_mapper
{
	public $taxonomy;
	public $searchby;
	public $createTerms;
	
	public function __construct($taxonomy=NULL, $searchby='slug',$createTerms=FALSE)
	{
		$this->taxonomy=$taxonomy;
		$this->searchby=$searchby;
		$this->createTerms=$createTerms;
	}
	
	public function map($input)
	{
		if ( $input == null ) { return null; }
		if(!is_array($input) && $this->taxonomy!=NULL)
		{
			$searchby=$this->searchby;
			$input=array($searchby=>htmlentities($input),'taxonomy'=>$this->taxonomy);
		}
		if(is_array($input))
		{
			$taxonomy=$input['taxonomy'];
			if(isset($input['slug']))
			{
				$slug=$input['slug'];
				$obj=get_term_by('slug',$slug,$taxonomy);
				if( is_object( $obj ) ) {
					return $obj->term_id;
				}
			}
			else if(isset($input['name']))
			{
				$name=$input['name'];
				$obj=get_term_by('name',$name,$taxonomy);
				if( is_object( $obj ) ) {
					return $obj->term_id;
				}
			}
		}
		else
		{
			$obj = get_category_by_slug( $input );
			if ( is_object( $obj ) ) {
				return $obj->term_id;
			}
		}
		if($this->createTerms)
		{
			if(is_array($input))
			{
				if(isset($input['slug']))
				{
					$result=wp_insert_term($input['slug'],$input['taxonomy']);
                    return $result['term_id'];
				}
				else if(isset($input['name']))
				{
					$result=wp_insert_term($input['name'],$input['taxonomy']);
					if(!is_array($result)) {
					    var_dump($input);
					    var_dump($result);
                    }
                    return $result['term_id'];
				}
			}
			else
			{
				$result=wp_insert_term($input,$this->taxonomy);
                return $result['term_id'];
			}
		}
		return null;
	}
}