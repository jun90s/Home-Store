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
    <title>分类 - 仓库</title>
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
            <li class="active"><a href="/category/">分类</a></li>
            <li><a href="/setting/">偏好</a></li>
          </ul>
        </div>
      </div>
    </nav>
    <div class="container" style="margin:90px auto;">
      <div id="msgbox"></div>
      <form id="editor" action="/api/" method="post">
        <div class="row">
          <div class="col-md-3">
            <div class="form-group">
              <label>所属分类</label>
              <div id="category_list"></div>
            </div>
          </div>
          <div class="col-md-9">
            <input name="id" type="hidden" value="<?php if(isset($_REQUEST['id'])) echo $_REQUEST['id']; ?>">
             <input name="object" type="hidden" value="category">
             <input name="ref" type="hidden" value="/category/?id={id}">
            <div class="form-group">
              <label for="name">分类名称</label>
              <input id="name" name="name" type="text" class="form-control">
            </div>
            <div class="form-group">
              <label>属性模板</label>
              <table class="table table-hover" style="text-align:center">
                <thead>
                  <tr>
                    <td>名称</td>
                    <td>重要</td>
                  </tr>
                </thead>
                <tbody id="attributes"></tbody>
              </table>
            </div>
            <button id="submit" type="submit" class="btn btn-default">应用</button>
          </div>
        </div>
      </form>

    <script type="text/javascript">
      var attr_new_id = 0;
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
        var editor = $("#editor").children("div").children("div");
        var category_id = editor.children("input[name='id']").val();
        
        var category_list = null;
        
        $.ajax({
          url: "/api/?object=categories",
          async: false,
          cache: false,
          timeout: 30000,
          method: "get",
          dataType: "json",
          success: function(data, textStatus, jqXHR) {
            if(data['code']!=200) {
              $("#msgbox").append('<div class="alert alert-danger alert-dismissible" role="alert"><strong>危险!</strong> 您正在对无效数据进行操作。</div>');
              return;
            }
            category_list=data['data'];
          },
          error: function(jqXHR, textStatus, errorThrown) {
            $("#msgbox").append('<div class="alert alert-warning alert-dismissible" role="alert"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button><strong>警告!</strong> 数据加载失败，请刷新页面。</div>');
          }
        });
        
        function getCategory(list, category_id) {
          var result=null;
          if(list==null)
            return null;
          list.every(function(category) {
            if(category['id']==category_id) {
              result=category;
              return false; //break
            }
            if((tmp=getCategory(category['children'], category_id))!=null)
              result=tmp;
            return true;
          });
          return result;
        }
        
        
        function printCategoryList(list, depth, hidden, checked) {
          list.forEach(function(category) {
            for(i=0;i<hidden.length;i++)
              if(hidden[i]==category['id'])
                return;
            var input = $("<input>").attr("type", "radio").attr("name", "category").attr("value", category['id']);
            for(i=0;i<checked.length;i++)
              if(checked[i]==category['id'])
                input.prop("checked", true);
            $("#category_list")
              .append(
                $("<div></div>")
                .css("padding-left", depth+"em")
                .addClass("radio")
                .append(
                  $("<label></label>")
                  .append(input)
                  .append(category['name'])
                  .append($("<small> </small>").append($("<a></a>").attr("href", "/category/?id="+category['id']).text("修改")))
              )
            );
            printCategoryList(category['children'], depth+1, hidden, checked);
          });
        }

        function addAttribute(name_old, name, main, oninput, inherit) {
          ++attr_new_id;
          var attribute_line=$("<tr></tr>");
          var input_name=$("<input>").addClass("form-control").attr("name", "attr_name_"+attr_new_id).attr("style", "width:100%").attr("type", "text").attr("value", name).on("input", function(){handle_attribute_inputs(input_name)});
          var input_main=$("<input>").attr("name", "attr_main_"+attr_new_id).attr("style", "width:100%").attr("type", "checkbox");
          if(inherit) {
            attribute_line.addClass("disabled");
            input_name.attr("disabled", "disabled");
            input_main.attr("disabled", "disabled");
          } else {
            attribute_line.append($("<input>").attr("name", "attr_id[]").attr("type", "hidden").attr("value", attr_new_id));
            if(name_old!=null&&name_old.length>0)
              attribute_line.append($("<input>").attr("name", "attr_name_old_"+attr_new_id).attr("type", "hidden").attr("value", name_old));
          }
          attribute_line.append($("<td></td>").append(input_name));
          if(main==1)
            input_main.prop("checked", true);
          attribute_line.append($("<td></td>").append(input_main));
          if(inherit)
            $("#attributes").prepend(attribute_line);
          else
            $("#attributes").append(attribute_line);
        }
        
        
        function printInheritAttritube(id) {
          // 清理
          $("#attributes").children("tr[class='disabled']").remove();
          // 添加
          var category = getCategory(category_list, id);
          while(category!=null) {
            for(i=0;i<category['attr_name'].length;i++) {
              addAttribute("", category['attr_name'][i], category['attr_main'][i], function() {}, 1);
            }
            if(category['id']==category['parent'])
              break;
            category = getCategory(category_list, category['parent']);
          }
        }
        
        // Auto Add/Delete New Attributes
        function handle_attribute_inputs(e) {
          var old_name_flag = false;
          var last_flag = false;
          // attr_name_old exists?
          e.parent().parent().children("input").each(function(){
            if($(this).attr("name").indexOf("attr_name_old")==0) {
            	old_name_flag = true;
              return false;
            }
          });
          // last?
          last_flag=!e.parent().parent().next().is("tr");
          if(e.val().length == 0) {
            if(!old_name_flag && !last_flag) {
              e.parent().parent().remove();
            }
          } else if(last_flag) {
            addAttribute("", "", 0, function() {
              handle_attribute_inputs(e);
            }, 0);
          }
        }
        
        
        if(category_list==null)
          return;
          
        // find parent
        var category_parent = 0;
        if(category_id.length > 0) {
          category = getCategory(category_list, category_id);
          if(category != null) {
            category_parent= category['parent'];
          }
        } else {
          category_parent = category_list[0]['id'];
        }
        
        // print list  
        if(category_id.length > 0) {
            printCategoryList(category_list, 0, [category_id], [category_parent]);
        } else {
          printCategoryList(category_list, 0, [], [category_parent]);
	      }
        
        // Auto Print Inherit Attritubes
        $("input[name='category']").change(function() {
          printInheritAttritube($(this).val());
        });

        // print attributes
        if(category_id!=category_parent)
	        printInheritAttritube(category_parent);
	      if(category_id.length > 0) {
	        var category = getCategory(category_list, category_id);
	        if(category==null) {
	          $("#msgbox").append('<div class="alert alert-danger alert-dismissible" role="alert"><strong>危险!</strong> 您正在对无效数据进行操作。</div>');
	          return;
	        }
	        editor.children("div").children("input[name='name']").attr("value", category['name']);
	        for(i=0;i<category['attr_name'].length;i++) {
            addAttribute(category['attr_name'][i], category['attr_name'][i], category['attr_main'][i], function() {}, 0);
          }
	      }
        addAttribute("", "", 0, function(){handle_attribute_inputs($(this))}, 0);
        
      });
    </script>
  </body>
</html>



