Spri Naver search
====

How to Use
----

`[spri-naver-search query="your search term"]`

you can use conditional search by wrapping query with single quote(`'`) like `query='"this text must be in result"'`. notice that only double quote has effect on search. single quoted string treated like normal string.

All of the conditional search rules are [HERE](https://help.naver.com/support/contents/contents.nhn?serviceNo=606&categoryNo=1911).

### initial setup

after plugin activation, you should fill out API key option value at `General>SPRI Naver Search`  

### available attr
You can find attr reference from [Reference]

[Reference]:http://developer.naver.com/wiki/pages/SrchAPI

attr name|description
----------|-----------|
key| (optional)put your naver search api key. if not set, get api key from plugin option panel.
query| word or words with quote want to search
target| default is 'news'. you can find available parameters from [Reference] 
display| (optional) default is 10. determine how many results be displayed. max is 100.
start| (optional) default is 1. set starting page of search results. max is 1000.
sort|(optional) default is 'date'. set sorting method of results. **date** will sort result based on date. **sim** will sort result based on accuracy. 
class| (optional) default is 'spri-naver-search' set the class of template.
template| (optional) default is 'basic'. set the template of displaying result.
is_crawl| (optiona) default is 'n'. possible values are 'y' and 'n'. if this set to 'y' the plugin crawling whole page of the naver search result. Displayed result will be group by year and month. 

### edit template

you edit or add template in __template__ directory. in the template file, next parameters be used.

- `$item->title`: title of result .
- `$item->link`: link to result on naver.
- `$item->originallink`: link to original news(or service) provider
- `$item->description`: short description of result
- `$item->pubDate`: published date of result