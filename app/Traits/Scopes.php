<?php 

namespace App\Traits;

trait Scopes {

	public function scopeParent($query)
	{
		$query->whereNull('parent_id');
	}

	public function scopeChildren($query)
	{
		$query->whereNotNull('parent_id');
	}

	public function scopePremium($query)
	{
		$query->whereNotNull('parent_id');
	}

	public function scopeMerchantMember($query, $id = 0)
	{
		$query->where(['member_type' => 'merchant', 'member_id' => $id]);
	}

	public function scopeAgentMember($query, $id = 0)
	{
		$query->where(['member_type' => 'agent', 'member_id' => $id]);
	}
}