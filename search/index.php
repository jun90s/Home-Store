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
    <title>查询 - 仓库</title>
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
            <li class="active"><a href="/search/">查询</a></li>
            <li><a href="/item/">库存</a></li>
            <li><a href="/category/">分类</a></li>
            <li><a href="/setting/">偏好</a></li>
          </ul>
        </div>
      </div>
    </nav>
    <div class="container" style="margin:90px auto;">
      <div id="msgbox"></div>
      <form id="editor">
        <div class="row">
          <div class="col-md-3">
            <input name="object" type="hidden" value="items">
            <div class="form-group">
              <label>分类</label>
              <div id="category_list"></div>
            </div>
            <div class="form-group">
              <label>关键词</label>
              <div class="input-group">
                <input type="text" class="form-control" id="words" name="words">
                <span class="input-group-btn">
                  <button class="btn btn-default" type="button" id="searchBtn">搜索</button>
                </span>
              </div>
            </div>
          </div>
          <div class="col-md-9">
            <table id="item-list" class="table table-hover" style="text-align:center">
              <thead style="font-weight:bold"></thead>
              <tbody></tbody>
            </table>
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
        printCategoryList(category_list, 0, [], [category_list[0]['id']]);
        
        
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
              category_set.add(category['id']);
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
        
        
        
        
        var itemData = new Array();
        var attrSet = new Set();
        
        function refreshTable(refreshTableHead) {
          if(refreshTableHead) {
            $("#item-list").children().children("tr").remove();
        	  var tr = $("<tr></tr>");
            tr.append($("<td></td>").text("照片"));
            tr.append($("<td></td>").text("名称"));
            tr.append($("<td></td>").text("数量"));
            attrSet.forEach(function(attr) {
            	tr.append($("<td></td>").text(attr).data("attr", "1"));
            });
            $("#item-list").children("thead").append(tr);
            $("#item-list").children("thead").children("tr").children("td").click(function() {
              var sortType=1;
              if($(this).data("sort")=="1")
                sortType=-1;
              $(this).data("sort", sortType);
              if($(this).data("attr")=="1") {
                var attr_name=$(this).text();
                itemData.sort(function(a,b) {
                  var text1 = a.get("attribute").get(attr_name);
                  var text2 = b.get("attribute").get(attr_name);
                  if(text1==undefined)
                    text1="";
                  if(text2==undefined)
                    text2="";
                  var length = text1.length < text2.length ?  text1.length : text2.length;
                  var result = 0;
                  for(var i = 0; i < length; i++) {
                    if(text1.charCodeAt(i)!=text2.charCodeAt(i)) {
                      result=text1.charCodeAt(i)-text2.charCodeAt(i);
                      break;
                    }
                  }
                  if(result==0) {
                    if(text1.length < text2.length)
                      result=-1;
                    else if(text1.length > text2.length)
                      result=1;
                  }
                  return sortType*result;
                }); 
              } else if($(this).text()=="名称") {
                itemData.sort(function(a,b) {
                  var text1 = a.get("name");
                  var text2 = b.get("name");
                  var length = text1.length < text2.length ?  text1.length : text2.length;
                  var result = 0;
                  
                  for(var i = 0; i < length; i++) {
                    if(text1.charCodeAt(i)!=text2.charCodeAt(i)) {
                      result=text1.charCodeAt(i)-text2.charCodeAt(i);
                      break;
                    }
                  }
                  if(result==0) {
                    if(text1.length < text2.length)
                      result=-1;
                    else if(text1.length > text2.length)
                      result=1;
                  }
                  return sortType*result;
                });
              } else if($(this).text()=="数量") {
                itemData.sort(function(a,b) {
                  return sortType*(a.get("count")-b.get("count"));
                });
              }
              // output
              refreshTable(false);
            });
            
            
          } else {
            $("#item-list").children("tbody").children("tr").remove();
          }
          
          
          itemData.forEach(function(item) {
            var tr = $("<tr></tr>");
            tr.append($("<td></td>").append($("<img>").css("width", "50px").css("height", "50px").css("display", "inline-block").css("background", "#EEE").attr("src", item.get("photo"))));
            tr.append($("<td></td>").css("text-align", "left").append($("<a></a>").attr("href", "/item/?id="+item.get("id")).text(item.get("name"))));
            tr.append($("<td></td>").text(item.get("count")));
            attrSet.forEach(function(attr) {
              if(item.get("attribute").has(attr))
          	    tr.append($("<td></td>").text(item.get("attribute").get(attr)));
          	  else
          	    tr.append($("<td></td>"));
            });
            $("#item-list").children("tbody").append(tr);
          });
          
        }
       
        
        $("#searchBtn").click(function() {
          $.ajax({
            url: "/api/?"+$("#editor").serialize(),
            async: false,
            cache: false,
            timeout: 32000,
            method: "get",
            dataType: "json",
            success: function(data, textStatus, jqXHR) {
              if(data['code']!=200) {
                $("#msgbox").append('<div class="alert alert-warning alert-dismissible" role="alert"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button><strong>抱歉!</strong> 数据加载失败，请重试。</div>');
                return;
              }
              itemData = new Array();
              attrSet = new Set();
              data['data'].forEach(function(item) {
              	var itemMap = new Map();
                itemMap.set("id", item["id"]);
                itemMap.set("name", item["name"]);
                itemMap.set("count", item["count"]);
                itemMap.set("photo", item["photo"]);
                var itemAttrMap = new Map();
                item["attribute"].forEach(function(attr) {
                  attrSet.add(attr['name']);
                  itemAttrMap.set(attr['name'], attr['value']);
                });
                itemMap.set("attribute", itemAttrMap);
				        itemData.push(itemMap);
              });
              
              // output
              refreshTable(true);
              
              $("#item-list").children("thead").children("tr").children("td").eq(1).trigger("click")
            },
            error: function(jqXHR, textStatus, errorThrown) {
              $("#msgbox").append('<div class="alert alert-warning alert-dismissible" role="alert"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button><strong>抱歉!</strong> 数据加载失败，请重试。</div>');
            }
          });
          
          
        });
        
      });
      
    </script>
  </body>
</html>



