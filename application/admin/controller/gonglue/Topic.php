<?php

namespace app\admin\controller\gonglue;

use addons\gonglue\Gonglue;
use app\common\controller\Backend;
use think\Db;
use think\Request;

/**
 * 话题管理
 *
 * @icon fa fa-circle-o
 */
class Topic extends Backend
{

    /**
     * Topic模型对象
     * @var \app\admin\model\gonglue\Topic
     */
    protected $model = null;

    protected $noNeedLogin = ['hotTopic', 'recommendTopic', 'viewTopic', 'viewComment', 'addComment', 'addTopic', 'searchTopic', 'likeTopic', 'likeComment'];

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\gonglue\Topic;
        $this->view->assign("flagList", $this->model->getFlagList());
        $this->view->assign("statusList", $this->model->getStatusList());
    }

    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */


    /**
     * 获取热门话题
     */
    public function hotTopic(Request $request)
    {
        $page = $request->param('page');
        $num = $request->param('num');
        $topic = new \app\admin\model\gonglue\Topic();
        $hotTopic = $topic->order('comments', 'desc')->page($page, $num)->select();
        return json($hotTopic);
    }

    /**
     * 获取推荐话题
     */
    public function recommendTopic(Request $request)
    {
        $page = $request->param('page');
        $num = $request->param('num');
        $topic = new \app\admin\model\gonglue\Topic();
        $hotTopic = $topic->order('views', 'asc')->page($page, $num)->select();
        return json($hotTopic);
    }

    /**
     * 浏览单个话题
     */
    public function viewTopic(Request $request)
    {
        $data = $request->param('topic_id');

        $topicComment = new \app\admin\model\gonglue\Topiccomment();
        $count = $topicComment->where('topic_id', $data)->count();

        //更新评论条数
        $topic = new \app\admin\model\gonglue\Topic();
        $topic->where('id', $data)->setField('comments', $count);

        $viewData = $topic->where('id', $data)->find();
        $topic->where('id', $data)->setInc('views');

        return json($viewData);
    }

    /**
     * 浏览具体话题评论
    */
    public function viewComment(Request $request)
    {
        $data = $request->param('topic_id');
        $page = $request->param('page');
        $num = $request->param('num');

        $topicComment = new \app\admin\model\gonglue\Topiccomment();
        $commentData = $topicComment->where('topic_id', $data)->page($page, $num)->select();
        return json($commentData);
    }

    /**
     * 添加评论
     */
    public function addComment(Request $request)
    {
        $data = $request->param();

        $contentComment = new \app\admin\model\gonglue\Topiccomment();
        $result = $contentComment->addComment($data);

        if ($result == 1) {

            $id = $request->param('topic_id');
            $topic = new \app\admin\model\gonglue\Topic();
            $topic->where('id', $id)->setInc('comments');

            $this->success('评论添加成功');
        } else {
            $this->error($result);
        }
    }

    /**
     * 添加话题
     */
    public function addTopic(Request $request)
    {
        $data = $request->param();
        $topic = new \app\admin\model\gonglue\Topic();
        $result = $topic->addTopic($data);
        if ($result == 1) {
            $this->success('话题添加成功');
        } else {
            $this->error($result);
        }
    }

    /**
     * 搜索话题
     */
    public function searchTopic(Request $request)
    {
        $keywords = $request->param('keywords');  //获取搜索关键字
        $page = $request->param('page');
        $num = $request->param('num');

        $where['title|content'] = array('like','%'.$keywords.'%');  //用like条件搜索title和content两个字段
        $topic = new \app\admin\model\gonglue\Topic();
        $data =$topic->where($where)->page($page, $num)->select();
        return json($data);
    }

    /**
     * 话题点赞
     */
    public function likeTopic(Request $request) {
        $data = [
            'topic_id' => $request->param('topic_id'),
            'username' => $request->param('username')
        ];


        $topic = new \app\admin\model\gonglue\Topic();
        $topic->where('id', $data['topic_id'])->setInc('likes');

        $topicLike = new \app\admin\model\gonglue\Topiclike();
        $topicLike->save($data);

        $this->success('点赞成功');
    }

    /**
     * 评论点赞
    */
    public function likeComment(Request $request)
    {
        $data = [
            'comment_id' => $request->param('comment_id'),
            'username' => $request->param('username')
            ];

        $comment = new \app\admin\model\gonglue\Topiccomment();
        $comment->where('id', $data['comment_id'])->setInc('likes');

        $topicCommentLike = new \app\admin\model\gonglue\Topiccommentlike();
        $topicCommentLike->save($data);

        $this->success('点赞成功');
    }


    /**
     * 话题点赞状态
    */
    public function topicLikeStatus(Request $request) {
        $topic_id = $request->param('topic_id');
        $username = $request->param('username');

        $topicLike = new \app\admin\model\gonglue\Topiclike();
        $res = $topicLike->where('topic_id', $topic_id)->where('username', $username)->find();

        if (empty($res)) {
            return json([
                'code' => 0,
                'msg' => ''
            ]);
        } else {
            return json(
                [
                    'code' => 1,
                    'msg' => ''
                ]
            );
        }

    }

    /**
     * 话题评论点赞状态
    */
    public function topicCommentLikeStatus(Request $request) {
        $comment_id = $request->param('comment_id');
        $username = $request->param('username');

        $topicCommentLike = new \app\admin\model\gonglue\Topiccommentlike();
        $res = $topicCommentLike->where('comment_id', $comment_id)->where('username', $username)->find();

        if (empty($res)) {
            return json([
                'code' => 0,
                'msg' => ''
            ]);
        } else {
            return json(
                [
                    'code' => 1,
                    'msg' => ''
                ]
            );
        }

    }

}