
사용하는 법
----------

`[spri-naver-search query="검색어를 넣습니다"]`

위와 같이 숏코드를 사용하여 뉴스 검색결과를 포스트에 표시하게 할 수 있습니다. 홑따옴표(`'`)를 사용해서  `query='"결과에 반드시 포함"'` 처럼 검색어를 작성할 수 있습니다. 이때 쌍따옴표를 홑따옴표로 감싸야 제대로 적용이 되며, 반대의 경우에는 적용이 되지 않습니다.

이러한 상세 조건 검색에 대한 규칙은 [conditional search](https://help.naver.com/support/contents/contents.nhn?serviceNo=606&categoryNo=1911) 페이지에서 확인할 수 있습니다.

### 최초 설정

플러그인 설치를 마치고 난 후, 워드프레스의 관리자 패널에서 `General>SPRI Naver Search` 옵션 페이지의 검색 API 키를 채워 넣어야합니다.

### 사용법

#### 설정

![Imgur](http://i.imgur.com/7gz2boa.jpg)

플러그인 설치후 대시보드의 `SPRI Naver Search` 메뉴로 이동합니다.

![Imgur](http://i.imgur.com/yd5xWDK.jpg)

네이버 개발자 센터에서 발급받은 검색 API키를 입력후 저장합니다.

#### 숏코드

![Imgur](http://i.imgur.com/EiFKixz.jpg)

![Imgur](http://i.imgur.com/47bZcCa.jpg)

이제 숏코드를 사용할 수 있습니다. 위와 같이 숏코드를 파라메터를 지정할 수 있습니다. 두번째 사용방법과 같이 is_crawl 옵션을 y로 줄 경우, 대시보드에서 관리할 수 있으며, 위젯에서 사용할 수 있습니다.

![Imgur](http://i.imgur.com/xqLumh4.jpg)

![Imgur](http://i.imgur.com/NgXWtqg.jpg)

위 화면은 is_crawl이 y일때입니다. 아래 화면은 지정하지 않았을때 기본값인 n으로 설정된 모습니다. 크롤링을 할 경우, 년월로 조회를 할 수 있습니다. 하지 않을경우에는 숏코드에 지정된 숫자만큼만 표시하게 됩니다.

![Imgur](http://i.imgur.com/9lQQqHs.jpg)
![Imgur](http://i.imgur.com/YJNLsVl.jpg)

위와같이 년도 및 월별로 쿼리의 해당하는 뉴스 검색 결과를 볼 수 있습니다.

![Imgur](http://i.imgur.com/A4vrCe7.jpg)

크롤링된 뉴스 기사는, `SPRI Naver Search > Article Manage` 메뉴에서 관리할 수 있습니다.

![Imgur](http://i.imgur.com/qoHrMoy.jpg)

이처럼 숏코드에 크롤링하게 지정해둔 쿼리들을 볼 수 있습니다. 오른쪽의 `조회`를 누를면 해당 검색어로 가져와서 보여주는 뉴스들을 보여주게됩니다.

![Imgur](http://i.imgur.com/RQhWkkH.jpg)

![Imgur](http://i.imgur.com/RVtv4yF.jpg)

가져온 뉴스중에서 위젯이나 숏코드상에서 숨기고 싶은 뉴스가 있다면, On/Off 슬라이드 버튼을 클릭하여 상태를 바꾸면 됩니다. 따로 저장할 필요 없이, Off 상태가 되면 바로 숨겨지게 됩니다.

#### 위젯

`SPRI Naver Article Widget`을 추가하여 뉴스를 슬라이드쇼로 위젯에서 보여줄 수 있습니다.

![Imgur](http://i.imgur.com/9kJvszy.jpg)

위젯에서 설정가능한 옵션은, `Query`와 `Number`입니다. Query는 크롤링된 검색어들을 보여줍니다. Number로 몇개의 기사를 보여줄지 결정 할 수 있습니다.

![Imgur](http://i.imgur.com/MtMLzuj.jpg)

위젯은 위와같이 보이게 됩니다.



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

### 위젯
네이버 뉴스 검색 결과를 위젯으로 표시할 수 있습니다. 위젯 옵션에서 숏코드에 `is_crawl=y` 옵션을 가지고 있는 쿼리를 선택할 수 있습니다.

### 프로젝트 구조
```
├─css                    
├─img                    
├─js                     
├─lib                    
│  ├─bootstrap           
│  ├─bootstrap-switch    
│  └─owl-carousel        
└─template              : 기사를 표시할때 사용하는 템플릿 저장
```