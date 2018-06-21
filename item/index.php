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
    <title>库存 - 仓库</title>
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
            <li class="active"><a href="/item/">库存</a></li>
            <li><a href="/category/">分类</a></li>
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
             <input name="object" type="hidden" value="item">
             <input name="ref" type="hidden" value="/item/?id={id}">
            <div class="form-group">
              <label for="name">名称</label>
              <input id="name" name="name" type="text" class="form-control">
            </div>
            <div class="form-group">
              <label for="count">数量</label>
              <input id="count" name="count" type="number" class="form-control">
            </div>
            <div class="form-group" id="photobox">
              <label for="photo">照片</label>
              <div style="padding-bottom:10px">
                <img style="width:320px;height:320px;display:block; background:#EEE" />
              </div>
              <div style="padding-bottom:10px;display:none;">
                <video style="width:320px;display:block; background:#EEE" autoplay="autoplay"></video>
              </div>
              <div style="padding-bottom:10px;display:none;">
                <canvas style="width:320px;height:320px"></canvas>
              </div>
              <div><input name="photo" type="hidden" value=""></div>
              <div style="checkbox">
                <label style="font-weight:normal"><input type="checkbox"> 垂直翻转</label>
              </div>
              <button id="camera" type="button" class="btn btn-default">拍照</button>
            </div>
            <div class="form-group">
              <label>属性</label>
              <table class="table table-hover" style="text-align:center">
                <thead>
                  <tr>
                    <td>名称</td>
                    <td>值</td>
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
      attr_new_id=0;
      
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
        var item_id = editor.children("input[name='id']").val();
        // get categories
        var category_list = null;
        $.ajax({
          url: "/api/?object=categories",
          async: false,
          cache: false,
          timeout: 32000,
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
        
        // 库存数据
        var item = null;
        if(item_id.length > 0) {
          $.ajax({
            url: "/api/?object=item&id="+item_id,
            async: false,
            cache: false,
            timeout: 32000,
            method: "get",
            dataType: "json",
            success: function(data, textStatus, jqXHR) {
              if(data['code']!=200) {
                $("#msgbox").append('<div class="alert alert-danger alert-dismissible" role="alert"><strong>危险!</strong> 您正在对无效数据进行操作。</div>');
                return;
              }
              item=data['data'];
            },
            error: function(jqXHR, textStatus, errorThrown) {
              $("#msgbox").append('<div class="alert alert-warning alert-dismissible" role="alert"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button><strong>警告!</strong> 数据加载失败，请刷新页面。</div>');
            }
          });
        }
        
        // 渲染分类
        function printCategoryList(list, depth, hidden, checked) {
          list.forEach(function(category) {
            for(i=0;i<hidden.length;i++)
              if(hidden[i]==category['id'])
                return;
            var input = $("<input>").attr("type", "checkbox").attr("name", "category[]").attr("value", category['id']);
            for(i=0;i<checked.length;i++)
              if(checked[i]==category['id'])
                input.prop("checked", true);
            $("#category_list")
              .append(
                $("<div></div>")
                .css("padding-left", depth+"em")
                .addClass("checkbox")
                .append(
                  $("<label></label>")
                  .append(input)
                  .append(category['name'])
              )
            );
            printCategoryList(category['children'], depth+1, hidden, checked);
          });
        }
        if(item != null)
          printCategoryList(category_list, 0, [], item['category']);
        else
          printCategoryList(category_list, 0, [], []);
        
        
        // attr map
        function addAttribute(name_old, name, value, oninput, inherit) {
          ++attr_new_id;
          var attribute_line=$("<tr></tr>");
          var input_name=$("<input>").addClass("form-control").attr("name", "attr_name_"+attr_new_id).attr("style", "width:100%").attr("type", "text").attr("value", name).on("input", function(){handle_attribute_inputs(input_name)});
          var input_value=$("<input>").addClass("form-control").attr("name", "attr_value_"+attr_new_id).attr("style", "width:100%").attr("type", "text").attr("value", value);
          if(inherit) {
            //input_name.attr("readonly", "readonly");
          }
          if(name_old!=null&&name_old.length>0)
            attribute_line.append($("<input>").attr("name", "attr_name_old_"+attr_new_id).attr("type", "hidden").attr("value", name_old));
          
          attribute_line.append($("<input>").attr("name", "attr_id[]").attr("type", "hidden").attr("value", attr_new_id));
          attribute_line.append($("<td></td>").append(input_name));
          attribute_line.append($("<td></td>").append(input_value));
          $("#attributes").append(attribute_line);
        }
        
        // Auto Add/Delete New Attributes
        function handle_attribute_inputs(e) {
          var old_name_flag = false;
          var last_flag = false;
          // attr_name_old exists?
          e.parent().parent().children("input").each(function(){
            console.log($(this).attr("name"));
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
            addAttribute("", "", "", function() {
              handle_attribute_inputs(e);
            }, 0);
          }
        }
        
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
        
        function getCategorySet() {
          var category_set = new Set();
          $("#category_list").children("div").children("label").children("input[type='checkbox']").each(function() {
            if($(this).is(":checked")) {
              var category = getCategory(category_list, $(this).val());
              var parent_id = 0;
              while(category != null) {
                category_set.add(category['id']);
                if(category['id'] == category['parent'])
                  break;
                category = getCategory(category_list, category['parent']);
              }
            }
          });
          return category_set;
        }
        
        function getAttributeMap(categories) {
          var attribute_map = new Map();
          categories.forEach(function(category_id) {
            var category = getCategory(category_list, category_id);
            if(category != null)
              for(var i = 0; i < category.attr_name.length; i++)
                attribute_map.set(category.attr_name[i], "");
          });
          return attribute_map;
        }
        
        
        
        $("#category_list").children("div").children("label").children("input[type='checkbox']").change(function() {
          var categorySet = getCategorySet();
          categorySet.forEach(function(id) {
            $("#category_list").children("div").children("label").children("input[type='checkbox'][value='" + id + "']").prop("checked", true);
          });
          var attrMap = getAttributeMap(categorySet);
          
          if(item != null) {
            item['attribute'].forEach(function(attr) {
              attrMap.set(attr['name'], attr['value']);
            });
          }
          
          // clean
          $("#attributes").children("tr").remove();
          // add
          attrMap.forEach(function(value, name, mapObj) {
            console.log(value.length + " " + name);
            if(value.length > 0)
              addAttribute(name, name, value, function(){handle_attribute_inputs($(this))}, 1);
            else
              addAttribute("", name, value, function(){handle_attribute_inputs($(this))}, 1);
          });
          addAttribute("", "", "", function(){handle_attribute_inputs($(this))}, 0);
        });
        
        if(item != null) {
          var categorySet = getCategorySet();
          var attrMap = getAttributeMap(categorySet);
          item['attribute'].forEach(function(attr) {
            attrMap.set(attr['name'], attr['value']);
          });
          attrMap.forEach(function(value, name, mapObj) {
            if(value.length > 0)
              addAttribute(name, name, value, function(){handle_attribute_inputs($(this))}, 1);
            else
              addAttribute("", name, value, function(){handle_attribute_inputs($(this))}, 1);
          });
          if(item['photo'].length>0)
            $("#photobox").children("div").children("img").attr("src", item['photo']);
          editor.children("div").children("input[name='name']").attr("value", item['name']);
          editor.children("div").children("input[name='count']").attr("value", item['count']);
        }
        addAttribute("", "", "", function(){handle_attribute_inputs($(this))}, 0);
        
        $("#camera").data("action", 1);
        $("#camera").click(function(){
          var photobox = $("#photobox").children("div");
          var imgbox = photobox.children("img");
          var videobox = photobox.children("video");
          var canvasbox = photobox.children("canvas");
          var inputbox = photobox.children("input[type='hidden']");
          var checkbox = photobox.children("label").children("input[type='checkbox']");
          navigator.getUserMedia = navigator.getUserMedia || navigator.webkitGetUserMedia || navigator.mozGetUserMedia || navigator.msGetUserMedia;  
          window.URL = window.URL || window.webkitURL || window.mozURL || window.msURL;
          switch($("#camera").data("action")) {
            case 1:
              var exArray = [];
              if(navigator.getUserMedia) {
                navigator.getUserMedia({
                  'video': {
                    'optional': [{'sourceId': exArray[1]}]  
                  },  
                  'audio':false  
                  }, function(stream) {
                    imgbox.hide();
                    canvasbox.parent().hide();
                    videobox.parent().show();
                    $("#camera").data("action", 2);
                    if (videobox.mozSrcObject !== undefined) {
                      videobox.get(0).mozSrcObject = stream;  
                    } else {
                      videobox.get(0).src = window.URL && window.URL.createObjectURL(stream) || stream;  
                    }
                  }, function(e) {
                    $("#msgbox").append('<div class="alert alert-warning alert-dismissible" role="alert"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button><strong>警告!</strong> 无法开启相机，请检查您的设备。</div>');
                });
              } else {
                $("#msgbox").append('<div class="alert alert-warning alert-dismissible" role="alert"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button><strong>警告!</strong> 无法开启相机，请检查您的设备。</div>');
              }
              break;
            case 2:
              var vWidth=videobox.get(0).videoWidth;
              var vHeight=videobox.get(0).videoHeight;
              canvasbox.get(0).width=320;
              canvasbox.get(0).height=320;
              var sx=0, sy=0, sw;
              if(vWidth < vHeight) {
                sw = vWidth;
              } else {
                sw = vHeight;
              }
              sx=(vWidth-sw)/2;
              sy=(vHeight-sw)/2;
              canvasbox.width(320);
              canvasbox.height(320);
              var context = canvasbox.get(0).getContext('2d');
              if(checkbox.is(":checked")) {
                context.translate(0, 320);
                context.scale(1, -1);
              }
              context.drawImage(videobox.get(0), sx, sy, sw, sw, 0, 0, 320, 320);
              inputbox.attr("value", canvasbox.get(0).toDataURL("image/png"));
              //videobox.get(0).src=undefined;
              videobox.parent().hide();
              canvasbox.parent().show();
              $("#camera").data("action", 3);
              break;
            case 3:
              canvasbox.parent().hide();
              videobox.parent().show();
              $("#camera").data("action", 2);
              break;
          }
        });
      });
      
    </script>
  </body>
</html>



