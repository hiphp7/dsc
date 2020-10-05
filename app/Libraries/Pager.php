<?php

namespace App\Libraries;

class Pager
{
    private $total; //数据表中总记录数
    private $listRows; //每页显示行数
    private $limit;
    private $uri;
    private $pageNum; //页数
    private $config;
    private $config_zn = ['header' => "个记录", "prev" => "<i><<</i>上一页", "next" => "下一页<i>>></i>", "first" => "第一页", "last" => "最后一页"];
    private $config_en = ['header' => "个记录", "prev" => "<<", "next" => ">>", "first" => "First", "last" => "Last"];
    private $listNum = 8;
    private $id = 0;
    private $type = 0;
    private $pageType = 0;
    private $funName = '';
    private $libType = 0;
    private $page = 1;
    private $setFloorMax;
    private $pageCurrent;

    /*
     * $total
     * $listRows
     * $id, $type = 0, $pageType = 0 --zhuo
     */

    public function __construct($params = [])
    {
        /* 初始化数据 */
        $params['total'] = isset($params['total']) ? $params['total'] : 0;
        $params['listRows'] = isset($params['listRows']) ? $params['listRows'] : 10;
        $params['pa'] = isset($params['pa']) ? $params['pa'] : '';
        $params['id'] = isset($params['id']) ? $params['id'] : 0;
        $params['type'] = isset($params['type']) ? $params['type'] : 0;
        $params['page'] = isset($params['page']) ? $params['page'] : 1;
        $params['funName'] = isset($params['funName']) ? $params['funName'] : '';
        $params['pageType'] = isset($params['pageType']) ? $params['pageType'] : 0;
        $params['libType'] = isset($params['libType']) ? $params['libType'] : 0;
        $params['cfigType'] = isset($params['cfigType']) ? $params['cfigType'] : 0;
        $params['config_zn'] = isset($params['config_zn']) ? $params['config_zn'] : '';

        $this->total = $params['total'];
        $this->id = $params['id'];
        $this->type = $params['type'];
        $this->pageType = $params['pageType'];
        $this->funName = $params['funName'];
        $this->libType = $params['libType'];
        if ($params['config_zn']) {
            $this->config = $params['config_zn'];
        } else {
            if ($params['cfigType'] == 1) {
                $this->config = $this->config_en;
            } else {
                $this->config = $this->config_zn;
            }
        }

        $this->listRows = $params['listRows'];
        $this->uri = $this->getUri($params['pa']);

        if ($params['pageType'] == 0) { //zhuo
            $this->page = !empty($_GET["page"]) ? intval($_GET["page"]) : 1;
        } else {
            $this->page = !empty($params['page']) ? $params['page'] : 1;
        }

        $this->pageNum = ceil($this->total / $this->listRows) ? ceil($this->total / $this->listRows) : 1;
        $this->limit = $this->setLimit();

        $this->setFloorMax = $this->setFloorMax();
        $this->pageCurrent = $this->pageCurrent(); //当前条数
    }

    private function setLimit()
    {
        return "Limit " . ($this->page - 1) * $this->listRows . ", {$this->listRows}";
    }

    private function setFloorMax()
    {
        return ($this->pageNum - $this->page) * $this->listRows + $this->pageNum;
    }

    public function pageCurrent()
    {
        return $this->end() - $this->start() + 1;
    }

    private function getUri($pa)
    {
        $url = request()->server('REQUEST_URI') . (strpos(request()->server('REQUEST_URI'), '?') ? '' : "?") . $pa;
        $parse = parse_url($url);

        if (isset($parse["query"])) {
            parse_str($parse['query'], $params);
            unset($params["page"]);

            if (isset($parse['path'])) {
                $url = $parse['path'] . '?' . http_build_query($params);
            }
        }

        return $url;
    }

    public function __get($args)
    {
        if ($args == "limit") {
            return $this->limit;
        } else {
            return null;
        }
    }

    private function start()
    {
        if ($this->total == 0) {
            return 0;
        } else {
            return ($this->page - 1) * $this->listRows + 1;
        }
    }

    private function end()
    {
        return min($this->page * $this->listRows, $this->total);
    }

    private function first()
    {
        $html = '';
        if ($this->page > 1) {
            if ($this->pageType == 0) {
                $html .= "<li class='first'><a href='{$this->uri}&page=1'>{$this->config["first"]}</a></li>";
            } else {
                $html .= "<li class='first'><a href='javascript:" . $this->funName . "(1, " . $this->id . ", " . $this->type . ", " . $this->libType . ");'>{$this->config["first"]}</a></li>";
            }
        }

        return $html;
    }

    private function prev_new()
    {
        $html = '';
        if ($this->pageType == 0) {
            $html .= "<div class='item prev'><a href='{$this->uri}&page=" . ($this->page - 1) . "'><i class='iconfont icon-left'></i></a></div>";
        } else {
            $html .= "<div class='item prev'><a href='javascript:" . $this->funName . "(" . ($this->page - 1) . ", " . $this->id . ", " . $this->type . ", " . $this->libType . ");'><i class='iconfont icon-left'></i></a></div>";
        }
        return $html;
    }

    private function prev()
    {
        $html = '';

        if ($this->pageType == 0) {
            $html .= "<li class='previous'><a href='{$this->uri}&page=" . ($this->page - 1) . "'>{$this->config["prev"]}</a></li>";
        } else {
            $html .= "<li class='previous'><a href='javascript:" . $this->funName . "(" . ($this->page - 1) . ", " . $this->id . ", " . $this->type . ", " . $this->libType . ");'>{$this->config["prev"]}</a></li>";
        }

        return $html;
    }

    private function pageList()
    {
        $linkPage = "";

        $inum = floor($this->listNum / 2);

        for ($i = $inum; $i >= 1; $i--) {
            $page = $this->page - $i;

            if ($page < 1) {
                continue;
            }

            if ($this->pageType == 0) {
                $linkPage .= "<li><a href='{$this->uri}&page={$page}'>{$page}</a></li>";
            } else {
                $linkPage .= "<li><a href='javascript:" . $this->funName . "(" . $page . ", " . $this->id . ", " . $this->type . ", " . $this->libType . ");'>{$page}</a></li>";
            }
        }

        $linkPage .= "<li class='current'><a class='page_hover' spellcheck='false'>{$this->page}</a></li>";

        for ($i = 1; $i <= $inum; $i++) {
            $page = $this->page + $i;
            if ($page <= $this->pageNum) {
                if ($this->pageType == 0) {
                    $linkPage .= "<li><a href='{$this->uri}&page={$page}'>{$page}</a></li>";
                } else {
                    $linkPage .= "<li><a href='javascript:" . $this->funName . "(" . $page . ", " . $this->id . ", " . $this->type . ", " . $this->libType . ");'>{$page}</a></li>";
                }
            } else {
                break;
            }
        }

        return $linkPage;
    }

    private function pageList_new()
    {
        $linkPage = "";

        $inum = floor($this->listNum / 2);

        for ($i = $inum; $i >= 1; $i--) {
            $page = $this->page - $i;

            if ($page < 1) {
                continue;
            }

            if ($this->pageType == 0) {
                $linkPage .= "<div class='item'><a href='{$this->uri}&page={$page}'>{$page}</a></div>";
            } else {
                $linkPage .= "<div class='item'><a href='javascript:" . $this->funName . "(" . $page . ", " . $this->id . ", " . $this->type . ", " . $this->libType . ");'>{$page}</a></div>";
            }
        }

        $linkPage .= "<div class='item cur'><a spellcheck='false'>{$this->page}</a></div>";

        for ($i = 1; $i <= $inum; $i++) {
            $page = $this->page + $i;
            if ($page <= $this->pageNum) {
                if ($this->pageType == 0) {
                    $linkPage .= "<div class='item'><a href='{$this->uri}&page={$page}'>{$page}</a></div>";
                } else {
                    $linkPage .= "<div class='item'><a href='javascript:" . $this->funName . "(" . $page . ", " . $this->id . ", " . $this->type . ", " . $this->libType . ");'>{$page}</a></div>";
                }
            } else {
                break;
            }
        }

        return $linkPage;
    }

    private function next_new()
    {
        $html = '';

        if ($this->page == $this->pageNum) {
            $this->page = $this->page;
        } else {
            $this->page = $this->page + 1;
        }

        if ($this->pageType == 0) {
            $html .= " <div class='item next'><a href='{$this->uri}&page=" . ($this->page) . "'><i class='iconfont icon-right'></i></a></div> ";
        } else {
            $html .= " <div class='item next'><a href='javascript:" . $this->funName . "(" . ($this->page) . ", " . $this->id . ", " . $this->type . ", " . $this->libType . ");'><i class='iconfont icon-right'></i></a></div> ";
        }

        return $html;
    }

    private function next()
    {
        $html = '';

        if ($this->page == $this->pageNum) {
            $this->page = $this->page;
        } else {
            $this->page = $this->page + 1;
        }

        if ($this->pageType == 0) {
            $html .= "<li class='nextious'><a href='{$this->uri}&page=" . ($this->page) . "'>{$this->config["next"]}</a></li>";
        } else {
            $html .= "<li class='nextious'><a href='javascript:" . $this->funName . "(" . ($this->page) . ", " . $this->id . ", " . $this->type . ", " . $this->libType . ");'>{$this->config["next"]}</a></li>";
        }

        return $html;
    }

    private function last()
    {
        $html = '';
        if ($this->page != $this->pageNum) {
            if ($this->pageType == 0) {
                $html .= "<li class='last'><a href='{$this->uri}&page=" . ($this->pageNum) . "'>{$this->config["last"]}</a></li>";
            } else {
                $html .= "<li class='last'><a href='javascript:" . $this->funName . "(" . ($this->pageNum) . ", " . $this->id . ", " . $this->type . ", " . $this->libType . ");'>{$this->config["last"]}</a></li>";
            }
        }

        return $html;
    }

    private function goPage()
    {
        return '&nbsp;&nbsp;<input type="text" onkeydown="javascript:if(event.keyCode==13){var page=(this.value>' . $this->pageNum . ')?' . $this->pageNum . ':this.value;location=\'' . $this->uri . '&page=\'+page+\'\'}" value="' . $this->page . '" style="width:25px"><input type="button" value="GO" onclick="javascript:var page=(this.previousSibling.value>' . $this->pageNum . ')?' . $this->pageNum . ':this.previousSibling.value;location=\'' . $this->uri . '&page=\'+page+\'\'">&nbsp;&nbsp;';
    }

    public function fpage($display = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9])
    {
        $html[0] = "";
        $html[9] = "";

        //$html[0]="<ul><li class='record_number'>共有<b>{$this->total}</b>{$this->config["header"]}</li>";
        $html[1] = "<li><div>当前页<b>" . ($this->end() - $this->start() + 1) . "</b>条</div></li>";
        $html[2] = "<li><div><b>{$this->page}/{$this->pageNum}</b>页</div></li>";

        $html[3] = $this->first();
        $html[4] = $this->prev_new();
        $html[5] = $this->pageList_new();
        $html[6] = $this->next_new();
        $html[7] = $this->last();
        $html[8] = $this->goPage();

        $fpage = '';
        foreach ($display as $index) {
            $fpage .= $html[$index];
        }

        return $fpage;
    }
}
