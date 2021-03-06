<?php

namespace Db\Feed;

use Core\Query;

class GetByPostId extends Query
{

	protected $userId;
	protected $postId;

	protected function build()
	{
		$query = '
			SELECT
				p.id,
				p.root_post_id,
				p.parent_post_id,
				p.user_id,
				p.group_id,
				p.visibility,
				p.content,
				p.image_file_id,
				p.attachment_file_id,
				p.created,
				p.modified,
				u.first_name,
				u.last_name,
				IFNULL(u.portrait_file_id, cnfg.value) AS portrait_file_id,
				IF(IFNULL(ml.user_id, 0) = 0, 0, 1) AS liked,
				IFNULL(al.like_count, 0) AS likes,
				COUNT(c.id) AS comments
			FROM
				posts p
			JOIN users u ON u.id = p.user_id
			JOIN configs cnfg ON cnfg.key = \'default_portrait_id\'
			LEFT JOIN posts c ON c.parent_post_id = p.id
			LEFT JOIN likes ml ON ml.post_id = p.id AND ml.user_id = ?
			LEFT JOIN (
				SELECT
					post_id,
					COUNT(*) AS like_count
				FROM
					likes
				GROUP BY
					post_id
			) al ON al.post_id = p.id
			WHERE
				p.id = ?
			GROUP BY
				p.id
			ORDER BY
				p.created DESC
			LIMIT 10';

		$this->addBind($this->userId);
		$this->addBind($this->postId);

		return $query;
	}

	/**
	 * @param mixed $userId
	 */
	public function setUserId($userId)
	{
		$this->userId = $userId;
	}

	/**
	 * @param mixed $postId
	 */
	public function setPostId($postId)
	{
		$this->postId = $postId;
	}
}