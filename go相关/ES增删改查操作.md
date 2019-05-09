
```go
package main

import (
	"log"
	"os"
	"gopkg.in/olivere/elastic.v5"
	"context"
	"fmt"

	"encoding/json"
	"reflect"
)

var client *elastic.Client

var host ="http://192.168.154.201:9200"

type Employee struct{
	FirstName string `json:"firstname"`
	LastName  string `json:"lastname"`
	Age       int    `json:"age"`
	About     string  `json:"about"`
	Interests []string `json:"interests"`
}



/**
初始化es驱动
 */
func init()  {
	errorlog := log.New(os.Stdout,"app",log.LstdFlags)
	var err error
	client,err = elastic.NewClient(elastic.SetErrorLog(errorlog),elastic.SetURL(host))
	if err !=nil{
		panic(err)
	}
	info,code,err :=client.Ping(host).Do(context.Background())
	if err != nil{
		panic(err)
	}
	fmt.Printf("Es retured with code %d and version %s\n",code,info.Version.Number)

	esversionCode,err := client.ElasticsearchVersion(host)
	if err != nil{
		panic(err)
	}
	fmt.Printf("es version %s\n",esversionCode)

}

//创建
func create(){
   //1使用结构体方式存入到es里面
  el := Employee{"Jane","Smith",32,"I like collect rock music",[]string{"music"}}

  put,err :=client.Index().Index("info").Type("employee").Id("1").BodyJson(el).Do(context.Background())
   if err != nil{
   	panic(err)
   }
   fmt.Printf("indexed %s to index %s,type %s\n",put.Id,put.Index,put.Type)
  }


//创建
func create1(){
	//1使用结构体方式存入到es里面
	//el := Employee{"Jane","Smith",32,"I like collect rock music",[]string{"music"}}
  e1:=`{"firstname":"john","lastname":"smith","age":22,"about":"I like play comeputer","interests":["computer","music"]}`
	put,err :=client.Index().Index("info").Type("employee").Id("2").BodyJson(e1).Do(context.Background())
	if err != nil{
		panic(err)
	}
	fmt.Printf("indexed %s to index %s,type %s\n",put.Id,put.Index,put.Type)
}

//查找
func get(){
	get,err := client.Get().Index("info").Type("employee").Id("1").Do(context.Background())
	if err != nil{
		panic(err)
	}
	if get.Found{
		fmt.Printf("got document %s in version %d from index %s,type %s\n",get.Id,get.Version,get.Index,get.Type)
	}
}

func update(){
	res ,err :=client.Update().Index("info").Type("employee").Id("1").Doc(map[string]interface{}{"age":88}).Do(context.Background())
	if err!=nil {
		fmt.Println(err.Error())
	}
	fmt.Printf("upate age %s\n",res.Result)
}

func delete(){
	res,err := client.Delete().Index("info").Type("employee").Id("1").Do(context.Background())
	if err != nil{
		fmt.Println(err.Error())
		return
	}
	fmt.Printf("delete result %s",res.Result)
}

func query(){
	var res *elastic.SearchResult
	var err error
	res,err = client.Search("info").Type("employee").Do(context.Background())
	printEmployee(res,err)

}

func query1()  {
	var res *elastic.SearchResult
	var err error
	 q := elastic.NewQueryStringQuery("lastname:smith")
	res,err = client.Search("info").Type("employee").Query(q).Do(context.Background())
	printEmployee(res,err)

	if res.Hits.TotalHits >0{
		fmt.Printf("found a total of %d Employee\n",res.Hits.TotalHits)

		for _,hit := range res.Hits.Hits{
			var t Employee
			err := json.Unmarshal(*hit.Source,&t) //这是另一种取出方式
			if err != nil{
				fmt.Println("failed")
			}
			fmt.Printf("employee name %s:%s\n",t.FirstName,t.LastName)
		}

	}else{
		fmt.Printf("found no employee\n")
	}

	}
//年龄大于30的
func query3(){
	var res *elastic.SearchResult
	var err error

	boolq:=elastic.NewBoolQuery()
	boolq.Must(elastic.NewMatchQuery("lastname","smith"))
	boolq.Filter(elastic.NewRangeQuery("age").Gt(30))
	res,err = client.Search("info").Type("employee").Query(boolq).Do(context.Background())
	printEmployee(res,err)


}

//短语包含like的
func query4(){
	var res *elastic.SearchResult
	var err error
	matchPhrase:=elastic.NewMatchPhraseQuery("about","music")
	res,err =client.Search("info").Type("employee").Query(matchPhrase).Do(context.Background())
    printEmployee(res,err)
	}

func aggs1()  {
	var res *elastic.SearchResult
	var err error
	//分析 interests	agg := NewMinAggregation().Field("price")
	aggs := elastic.NewAvgAggregation().Field("Age")
	res, err = client.Search("date_profile").Type("zhenai").Aggregation("avg_grade", aggs).Do(context.Background())
	printEmployee(res, err)
}

/**
 查询最小值
 */
func mintest()  {
	var res *elastic.SearchResult
	var err error
	//分析 interests	agg := NewMinAggregation().Field("price")
	aggs := elastic.NewMaxAggregation().Field("Age")
	res, err = client.Search("date_profile").Type("zhenai").Aggregation("avg_min", aggs).Do(context.Background())
	 if err != nil{
	 	panic(err)
	 }

	// minRetweets
	minAggRes, found := res.Aggregations.Min("avg_min")
	if !found {
		//fmt.Printf("%#v\n",res.Aggregations.Min("avg_min"))
	}
	if minAggRes == nil {
		//fmt.Printf("%#v\n",res.Aggregations.Min("avg_min"))
	}
	if minAggRes.Value == nil {
		fmt.Printf("%#v\n",*minAggRes.Value)
	}
	if *minAggRes.Value != 0.0 {
		fmt.Printf("%#v\n",*minAggRes.Value)
	}
	//fmt.Printf("%#v\n",res.Aggregations.Min("avg_min"))
	//printEmployee(res, err)
}

//聚合
func groupAvg1()  {

	// Match all should return all documents
	all := elastic.NewMatchAllQuery()
	var res *elastic.SearchResult
	var err error
	//分析 interests	agg := NewMinAggregation().Field("price")
	//usersAgg := elastic.NewTermsAggregation().Field("Marriage").Size(10).OrderByCountDesc()
	avgRetweetsAgg := elastic.NewAvgAggregation().Field("Age")

	aggs := elastic.NewMaxAggregation().Field("Age")
	res,err = client.Search("date_profile").Type("zhenai").Query(all).Pretty(true).Aggregation("avg_grade", aggs).Aggregation("Age",avgRetweetsAgg).Do(context.Background())
	if err != nil{
		panic(err)
	}
	fmt.Printf("%#v\n",res)

	// minRetweets
	manAggRes, found := res.Aggregations.Min("avg_grade")
	if !found {
		//fmt.Printf("%#v\n",res.Aggregations.Min("avg_min"))
	}
	if manAggRes == nil {
		//fmt.Printf("%#v\n",res.Aggregations.Min("avg_min"))
	}
	if manAggRes.Value == nil {
		fmt.Printf("%#v\n",*manAggRes.Value)
	}
	if *manAggRes.Value != 0.0 {
		fmt.Printf("%#v\n",*manAggRes.Value)
	}
	// minRetweetsfilterAggRes.Avg
	minAggRes, found := res.Aggregations.Avg("Age")
	if !found {
		//fmt.Printf("%#v\n",res.Aggregations.Min("avg_min"))

	}
	if minAggRes == nil {
		//fmt.Printf("%#v\n",res.Aggregations.Min("avg_min"))
	}
	if minAggRes.Value == nil {
		fmt.Printf("%#v\n",*minAggRes.Value)
	}
	if *minAggRes.Value != 0.0 {
		fmt.Printf("%#v\n",*minAggRes.Value)
	}
	//fmt.Printf("%#v\n",res.Aggregations.Min("avg_min"))
	//printEmployee(res, err)
}

//分组

func groupbyGeder()  {
	all := elastic.NewMatchAllQuery()
	var res *elastic.SearchResult
	var err error
	usersAgg := elastic.NewTermsAggregation().Field("Gender.keyword")

	histogram := elastic.NewTermsAggregation().Field("Marriage.keyword")

	usersAgg = usersAgg.SubAggregation("history", histogram)

	res,err = client.Search("date_profile").Type("zhenai").Query(all).Pretty(true).Aggregation("avg_grade", usersAgg).Do(context.Background())
	if err != nil{
		panic(err)
	}
	//fmt.Printf("%#v\n",res.Aggregations)

	agg, found := res.Aggregations.Terms("avg_grade")

	if !found {
		log.Fatalf("we should have a terms aggregation called %q", "avg_grade")
	}

	for _, userBucket := range agg.Buckets {
		// Every bucket should have the user field as key.
		user := userBucket.Key
		fmt.Printf("%#v\n",user)
		//fmt.Printf("user %q has %d tweets in %q\n", user, userBucket.DocCount, userBucket.Key)
		// The sub-aggregation history should have the number of tweets per year.
		histogram, found := userBucket.Terms("history")
		if found {
			for _, year := range histogram.Buckets {
				/*var key string
				if v := year.KeyAsString; v != nil {
					key = *v
				}*/
				fmt.Printf("user %q has %d tweets in %q\n", user, year.DocCount, year.Key)
			}
		}
	}

		/*termsAggRes, found := res.Aggregations.Terms("avg_grade")
		termsAggRes
		fmt.Printf("%#v\n",len(termsAggRes.Buckets))
		if !found {
			fmt.Errorf("expected %v; got: %v", true, found)
		}
		if termsAggRes == nil {
			fmt.Printf("expected != nil; got: nil")
		}
		if len(termsAggRes.Buckets) != 2 {
			fmt.Printf("expected %d; got: %d", 2, len(termsAggRes.Buckets))
		}
		if termsAggRes.Buckets[0].Key != "olivere" {
			fmt.Printf("expected %q; got: %q", "olivere", termsAggRes.Buckets[0].Key)
		}
		if termsAggRes.Buckets[0].DocCount != 2 {
			fmt.Printf("expected %d; got: %d", 2, termsAggRes.Buckets[0].DocCount)
		}
		if termsAggRes.Buckets[1].Key != "sandrae" {
			fmt.Printf("expected %q; got: %q", "sandrae", termsAggRes.Buckets[1].Key)
		}
		if termsAggRes.Buckets[1].DocCount != 1 {
			fmt.Printf("expected %d; got: %d", 1, termsAggRes.Buckets[1].DocCount)
		}*/
}




//打印查询的employee
func printEmployee(res *elastic.SearchResult,err error)  {
  if err != nil{
  	print(err.Error())
  	return
  }
  var typ Employee
  for _,item := range res.Each(reflect.TypeOf(typ)){
       t := item.(Employee)
       fmt.Printf("%#v\n",t)
  }
}
//分页操作
func list(size,page int){
	var res *elastic.SearchResult
	var err error
	if size < 0 || page < 1{
		fmt.Printf("param error")
		return
	}
	res, err = client.Search("info").Type("employee").Size(size).From((page-1)*size).Do(context.Background())
	printEmployee(res,err)
}


func aggv(){

	all := elastic.NewMatchAllQuery()

	agg := elastic.NewAvgAggregation().Field("Age")


	res,_ := client.Search().Index("date_profile").Type("zhenai").Query(all).Pretty(true).Aggregation("all_interests",agg).Do(context.Background())
	if res.Hits.TotalHits >0{
		fmt.Printf("found a total of %v Employee\n",res.Hits.Hits)

		for _,hit := range res.Hits.Hits{
			/*var t Employee
			err := json.Unmarshal(*hit.Source,&t) //这是另一种取出方式
			if err != nil{
				fmt.Println("failed")
			}*/
			fmt.Printf("employee name %v\n",hit)
		}

	}else{
		fmt.Printf("found no employee\n")
	}

	//fmt.Printf("%#v\n",res)
}



func main()  {
	 //create()
	//create1()
	//update()
	//delete()
	//query4()
	 //aggs1()
	//list(2,1)
	//aggv()
	//aggs1()
	//mintest()
	//groupAvg1()
	groupbyGeder()
}


```


