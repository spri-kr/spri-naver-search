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

# 한국어

사용하는 법
----------

`[spri-naver-search query="검색어를 넣습니다"]`

위와 같이 숏코드를 사용하여 뉴스 검색결과를 포스트에 표시하게 할 수 있습니다. 홑따옴표(`'`)를 사용해서  `query='"결과에 반드시 포함"'` 처럼 검색어를 작성할 수 있습니다. 이때 쌍따옴표를 홑따옴표로 감싸야 제대로 적용이 되며, 반대의 경우에는 적용이 되지 않습니다.

이러한 상세 조건 검색에 대한 규칙은 [참조][conditional search] 페이지에서 확인할 수 있습니다.

### 최초 설정

플러그인 설치를 마치고 난 후, 워드프레스의 관리자 패널에서 `General>SPRI Naver Search` 옵션 페이지의 검색 API 키를 채워 넣어야합니다.

### 숏코드에 사용 가능한 옵션

아래 옵션에 대한 정보는 [레퍼런스][Reference]페이지에서 확인할 수 있습니다.

[Reference]:http://developer.naver.com/wiki/pages/SrchAPI

옵션 이름|설명
----------|-----------|
key| (optional)옵션 페이지에서 지정한것과 다른 검색 API키를 지정할 때 사용합니다.
query| 검색하려는 단어나 문장을 따옴표로 감싸서 지정합니다.
target| (optional) 기본값은 'news'입니다. 각각의 대상에 따른 파라메터 정보는 [Reference]에서 찾을 수 있습니다. 
display| (optional) 기본값은 10입니다. 검색결과가 몇개 보여질지 결정할 수 있습니다. 최대는 100입니다.
start| (optional) 기본값은 1입니다. 몇번째 검색결과에서부터 보여줄지 결정할 수 있습니다. 최대는 1000입니다..
sort|(optional) 기본값은 'date'입니다. 결과의 정렬방법을 결정합니다. **date** 는 검색 결과를 날짜 기준으로 정렬합니다. **sim** 는 검색결과를 정확도 순으로 정렬합니다. 
class| (optional) 기본값은 'spri-naver-search'입니다. 템플릿에 적용될 클래스를 설정합니다.
template| (optional) 기본값은 'basic'입니다. 검색결과를 표시할 템플릿을 지정합니다.
is_crawl| (optional) 기본값은 'n'입니다. 'y'와 'n'중 하나를 선택할 수 있습니다. 'y'로 설정됐을 경우, 설정한 다른 모든 옵션은 무시됩니다. 플러그인은 검색 결과를 크롤링하여 년월로 그룹을 나누어 보여주게 됩니다. 크롤링은 하루에 두번 실행됩니다. 

### 템플릿 수정
플러그인 하위 디렉토리 __template__ 에 있는 템플릿을 수정해서 사용할 수 있습니다. 다음 값들이 템플릿 안에서 사용됩니다.

- `$item->title`: 뉴스 제목
- `$item->link`: 네이버가 제공하는 링크
- `$item->originallink`: 언론사 기사 링크
- `$item->description`: 검색어를 포함한 기사 본문 일부
- `$item->pubDate`: 기사 발행 일시

### 크롤된 뉴스 기사 숨기기
`SPRI Naver Search > Article Manage`에서 어떤 기사를 보이고 숨길것인지 설정 할 수 있습니다.