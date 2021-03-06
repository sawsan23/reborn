<?php

namespace Comment\Controller\Admin;

use Comment\Model\Comments as Comment;
use Reborn\Form\Validation as Validation;
use TijsVerkoyen\Akismet\Akismet;

class CommentController extends \AdminController
{
	private  $adminurl;

	protected static $child_comment;

	public function before() 
	{
		$this->menu->activeParent('content');
		
		\Translate::load('comment::comment');

		$ajax = $this->request->isAjax();

		if ($ajax) {
			$this->template->partialOnly();
		}

		$this->template->style('comment.css', 'comment')
					   	->script('comment.js', 'comment');
		
	}

	/**
	 * Comment Index (Comment List)
	 *
	 * @return void
	 **/
	public function index($page = null)
	{
		//Pagination
		$options = array(
		    'total_items'       => Comment::where('status', '!=', 'spam')->count(),
		    'url'               => ADMIN_URL.'/comment/index',
		    'items_per_page'    => \Setting::get('admin_item_per_page'),
		    'uri_segment'		=> 4
		);

		$pagination = \Pagination::create($options);

		$comments = Comment::where('status', '!=', 'spam')
							->orderBy('created_at', 'desc')
							->skip(\Pagination::offset())
							->take(\Pagination::limit())
							->get();

		$akismet_status = self::checkAkismet();

		$this->template->title('Manage Comments')
						->set('comments', $comments)
						->set('pagination', $pagination)
						->set('akismet_status', $akismet_status)
						//->script('plugins/jquery.colorbox.js')
						->set('list_type', 'all')
						->setPartial('admin/index');
	}

	/**
	 * Filter comments by Status
	 *
	 * @return void
	 **/
	public function filter($status, $page = null)
	{
		$options = array(
		    'total_items'       => Comment::where('status', $status)->count(),
		    'url'               => ADMIN_URL.'/comment/filter/'.$status,
		    'items_per_page'    => \Setting::get('admin_item_per_page'),
		    'uri_segment'		=> 5
		);

		$pagination = \Pagination::create($options);

		$comments = Comment::where('status', $status)
					->orderBy('created_at', 'desc')
					->skip(\Pagination::offset())
					->take(\Pagination::limit())
					->get();

		$akismet_status = self::checkAkismet();

		$page_title = ucfirst($status).' Comments';

		$this->template->title($page_title)
						->set('comments', $comments)
						->set('pagination', $pagination)
						->set('list_type', $status)
						->set('akismet_status', $akismet_status)
						//	->script('plugins/jquery.colorbox.js')
						->setPartial('admin/index');
	}

	/**
	 * Change Comment Status
	 *
	 * @return void
	 **/
	public function changeStatus($id, $status = null)
	{
		$comment = Comment::find($id);

		if ($comment->status == 'spam') {
			$spam = true;
		} else {
			$spam = false;
		}

		if ($status != null) {

			$comment->status = $status;
		} else {

			$comment->status = ($comment->status == 'approved') ? 'pending' : 'approved';
		}

		if ($comment->status == 'approved' and $comment->parent_id != 0) {
			$parent_status = Comment::where('id', $comment->parent_id)->pluck('status');
			if ($parent_status != 'approved') {
				\Flash::error(sprintf(t('comment::comment.message.error.parent_not_approved'), $comment->parent_id));
				return \Redirect::to(adminUrl('comment'));
			}
		}

		$save = $comment->save();

		if ($save) {
			$child_ele = self::getChild($id);
			if ($child_ele != null) {
				foreach ($child_ele as $child) {
					$child_com = Comment::find($child);
					if ($child_com->status != 'spam') {
						$child_com->status = $comment->status;
						$child_com->save();
					}
				}
			}
			if ($spam) {
				\Flash::warning(t('comment::comment.message.warning.approve_spam'));
			} else {
				\Flash::success(t('comment::comment.message.success.change_status'));
			}
		} else {
			\Flash::error(t('comment::comment.message.error.general'));
		}

		return \Redirect::to(adminUrl('comment'));

	}


	/**
	 * Get child comments
	 *
	 * @return void
	 **/
	protected function getChild($id) {

		$child_ele = Comment::where('parent_id', $id)->get();

		if (count($child_ele) > 0) {
			foreach($child_ele as $ele) {
				static::$child_comment[] = $ele->id;
				$grand_child = Comment::where('parent_id', $ele->id)->get()->count();
				if ($grand_child > 0) {
					self::getChild($ele->id);
				}
			}
		}
		return static::$child_comment;
	}

	/**
	 * Reply Comments from Admin Panel
	 *
	 * @return void 
	 **/
	public function reply($id = 0)
	{
		if (\Input::isPost()) {
			$or_comment = Comment::find(\Input::get('id'));

			$comment = new Comment;
			$comment->user_id = \Sentry::getUser()->id;
			$comment->value = \Input::get('message');
			$comment->module = $or_comment->module;
			$comment->content_id = $or_comment->content_id;
			$comment->status = "approved";
			$comment->parent_id = $or_comment->id;
			$comment->ip_address = \Input::ip();
			$save = $comment->save();
			if ($save) {
				return "true";
			} else {
				return "false";
			}
		}

		$comment = Comment::find($id);

		if ($comment == null) {
			return "<div class='ajax-error'>t('comment::comment.message.error.not_exist')</div>";
		}

		$this->template->setPartial('admin/reply-edit')
						->set('comment', $comment)
						->set('method', 'reply');

	}

	public function edit($id = 0)
	{
		if (\Input::isPost()) {
			$comment = Comment::find(\Input::get('id'));
			$comment->value = \Input::get('message');
			$comment->edit_user = \Sentry::getUser()->id;
			$save = $comment->save();
			if ($save) {
				return "true";
			} else {
				return "false";
			}
		}

		$comment = Comment::find($id);

		if ($comment == null) {
			return "<div class='ajax-error'>t('comment::comment.message.error.not_exist')</div>";
		}

		$this->template->setPartial('admin/reply-edit')
						->set('comment', $comment)
						->set('method', 'edit');
	}



	public function multiaction()
	{
		$method = \Input::get('sel_multi_action');

		if ($method == 'delete') {
			self::delete();
		} else {

			$ids = \Input::get('action_to');
			$comments = array();
			foreach ($ids as $id) {
				if ($comment = Comment::find($id)) {
					$comment->status = $method;
					$save = $comment->save();
					if ($save) {
						$child_ele = self::getChild($id);
						if ($child_ele != null) {
							foreach ($child_ele as $child) {
								$child_com = Comment::find($child);
								$child_com->status = $comment->status;
								$child_com->save();
							}
						}
						$comments[] = $id;
					}
				}
			}

			if (!empty($comments)) {
				if (count($comments) == 1) {
					\Flash::success(sprintf(t('comment::comment.message.success.multi_action'), $method));
				} else {
					\Flash::success(sprintf(t('comment::comment.message.success.multi_actions'), $method));
				}
			} else {
				\Flash::error(sprintf(t('comment::comment.message.error.multi_action'), $method));
			}
			return \Redirect::to(adminUrl('comment'));

		}
		
	}

	public function delete($id = null)
	{
		$ids = ($id) ? array($id) : \Input::get('action_to');

		$comments = array();

		foreach ($ids as $id) {
			if ($comment = Comment::find($id)) {
				$child_ele = self::getChild($id);
				if ($child_ele != null) {
					foreach ($child_ele as $child) {
						$child_com = Comment::find($child);
						$child_com->delete();
						$comments[] = (int)$child;
					}
				}
				$comment->delete();
				$comments[] = $id;
			}
		}

		if (!empty($comments)) {
			if (count($comments) == 1) {
				\Flash::success(t('comment::comment.message.success.delete'));
			} else {
				\Flash::success(t('comment::comment.message.success.multi_delete'));
			}
		} else {
			\Flash::error(t('comment::comment.message.error.delete'));
		}
		return \Redirect::to(adminUrl('comment'));

	}

	protected function checkAkismet() {

		$apiKey = \Setting::get('akismet_api_key');

		if ($apiKey == null) {
			return "no-key";
		} else {
			$url = rbUrl();
			try {
				$akismet = new Akismet($apiKey, $url);
				return $akismet->verifyKey();	
			} catch (\Exception $e) {
				return "no-connection";
			}
			
		}
	}
	
}
