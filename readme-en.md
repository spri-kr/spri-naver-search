Spri Naver search
====

[한국어](#한국어)

How to Use
----

`[spri-naver-search query="your search term"]`

you can use conditional search by wrapping query with single quote(`'`) like `query='"this text must be in result"'`. notice that only double quote has effect on search. single quoted string treated like normal string.

All of the conditional search rules are [HERE][conditional search]

[conditional search]:https://help.naver.com/support/contents/contents.nhn?serviceNo=606&categoryNo=1911

### initial setup

after plugin activation, you should fill out API key option value at `General>SPRI Naver Search`  

### How to use

#### Configuration

![Imgur](http://i.imgur.com/7gz2boa.jpg)

Go to `SPRI Naver Search` at admin dashboard. 

![Imgur](http://i.imgur.com/yd5xWDK.jpg)

put your search api key issued at [Naver developer center](http://developer.naver.com/wiki/pages/SrchAPI).

#### Shortcode

![Imgur](http://i.imgur.com/EiFKixz.jpg)

![Imgur](http://i.imgur.com/47bZcCa.jpg)

Now you can use shortcode on your post. you can set parameters at shortcode. if you set parameter `is_crawl` to `y`, you can manage result articles at `Article manage` menu and use articles at slide widget.

![Imgur](http://i.imgur.com/xqLumh4.jpg)

![Imgur](http://i.imgur.com/NgXWtqg.jpg)

first one shows when `is_crawl` set to `y`. second one shows `is_crawl` not set by user so set to default value `n`. if you set `is_crawl` to `y`, you can lookup articles by year and month. If you not set `is_crawl`, only `number` of articles displayed by shortcode. 

![Imgur](http://i.imgur.com/9lQQqHs.jpg)
![Imgur](http://i.imgur.com/YJNLsVl.jpg)

you can lookup articles by year and month dropdown selector.

![Imgur](http://i.imgur.com/A4vrCe7.jpg)

you can manage crawled articles at `SPRI Naver Search > Article Manage` menu.

![Imgur](http://i.imgur.com/qoHrMoy.jpg)

you can see queries set by shortcode with `is_crawl` set to y. click `조회` to show article by selected query.

![Imgur](http://i.imgur.com/RQhWkkH.jpg)

![Imgur](http://i.imgur.com/RVtv4yF.jpg)

if you want to hide a article at shortcode content or slide widget, you can toggle On/Off slide button to hide or show the article. This work does not need save button. 

#### Widget

You can add `SPRI Naver Article Widget` to show news articles on slide.

![Imgur](http://i.imgur.com/9kJvszy.jpg)

Widget configuration has two option `Query` and `Number`. `Query` shows queries from shortcode set `is_crawl` to `y`. `Number` determine how many articles showed up slide widget.

![Imgur](http://i.imgur.com/MtMLzuj.jpg)

configured widget look like this. 


### available attr in shortcode
You can find attr reference from [Reference]

[Reference]:http://developer.naver.com/wiki/pages/SrchAPI

attr name|description
----------|-----------|
key| (optional) put your naver search api key. if not set, get api key from plugin option panel.
query| word or words with quote want to search
target| (optional) default is 'news'. you can find available parameters from [Reference] 
display| (optional) default is 10. determine how many results be displayed. max is 100.
start| (optional) default is 1. set starting number of result of search results. max is 1000.
sort|(optional) default is 'date'. set sorting method of results. **date** will sort result based on date. **sim** will sort result based on accuracy. 
class| (optional) default is 'spri-naver-search' set the class of template.
template| (optional) default is 'basic'. set the template of displaying result.
is_crawl| (optional) default is 'n'. possible values are 'y' and 'n'. if this set to 'y' the plugin crawling whole page of the naver search result. Displayed result will be group by year and month. 

### edit template

you edit or add template in __template__ directory. in the template file, next parameters be used.

- `$item->title`: title of result .
- `$item->link`: link to result on naver.
- `$item->originallink`: link to original news(or service) provider
- `$item->description`: short description of result
- `$item->pubDate`: published date of result

### Hide crawled articles

you can hide some articles from `SPRI Naver Search > Article Manage` menu.

### Widget

You can display your search results on widget. At widget option, you can select query that set shortcode has `is_crawl=y` option.

### Project structure
```
├─css                    
├─img                    
├─js                     
├─lib                    
│  ├─bootstrap           
│  ├─bootstrap-switch    
│  └─owl-carousel        
└─template              : templates for displaying articles
```