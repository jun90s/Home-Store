<!--
Copyright 2017 Zhang Jun <jun90s@163.com>.

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
-->
<!DOCTYPE html>
<html lang="zh-CN">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>偏好 - 仓库</title>
    <link href="/css/bootstrap.min.css" rel="stylesheet">
    <script src="/js/jquery-3.2.1.min.js"></script>
    <script src="/js/jquery.cookie.js"></script>
    <script src="/js/bootstrap.min.js"></script>
    <style type="text/css">

    </style>
  </head>
  <body>
    <nav class="navbar navbar-inverse navbar-fixed-top">
      <div class="container">
        <div class="navbar-header">
          <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
            <span class="sr-only">仓库</span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </button>
          <a class="navbar-brand" href="/">仓库</a>
        </div>
        <div id="navbar" class="collapse navbar-collapse">
          <ul class="nav navbar-nav">
            <li><a href="/search/">查询</a></li>
            <li><a href="/item/">库存</a></li>
            <li><a href="/category/">分类</a></li>
            <li class="active"><a href="/setting/">偏好</a></li>
          </ul>
        </div>
      </div>
    </nav>
    <div class="container" style="margin:90px auto;">
      <div id="msgbox"></div>
      <form id="editor">
        <div class="form-group">
          <label for="name">名称</label>
          <div class="radio">
            <label><input type="radio" name="font_size" value="10"> 特大</label>
          </div>
          <div class="radio">
            <label><input type="radio" name="font_size" value="5"> 大</label>
          </div>
          <div class="radio">
            <label><input type="radio" name="font_size" value="0"> 标准</label>
          </div>
        </div>
      </form>

    <script type="text/javascript">
      $(document).ready(function() {
        var fontSize = $.cookie("font_size");
        if(typeof(fontSize)==undefined) {
          fontSize = 0;
          $.cookie("font_size", 0, {path: "/"});
        }
        function apply_big_text_mode(e) {
          if(typeof(e)!="object")
            return;
          e.children().each(function(){
            apply_big_text_mode($(this));
            $(this).css("font-size",(parseInt($(this).css("font-size").split("px")[0])+parseInt($.cookie("font_size")))+"px");
          });
        }
        apply_big_text_mode($("body"));
        $("input[name='font_size'][value='"+fontSize+"']").attr("checked", "checked");
        $("input[name='font_size']").change(function() {
          $.cookie("font_size", $(this).val(), {path: "/"});
          $("#msgbox").append('<div class="alert alert-success alert-dismissible" role="alert"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button><strong>应用成功!</strong> 请刷新页面。</div>');
        });
      });
    </script>
  </body>
</html>



