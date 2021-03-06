# SentenceParser

## HOW TO USE

```bash
> git clone https://github.com/Sinute/SentenceParser.git
> cd SentenceParser
> composer install
```

## EXAMPLE

```php
<?php
include 'vendor/autoload.php';
use Sinute\SentenceParser\SentenceParser;

$content = <<< DOC

小王说:“小李说:“小红说:“今天。天气不错啊。。。”。””。话里套话的情况下, 应该算一句话。
小王说:“嗨”，引号后面没有句号会被当做同一句话。
小王说:“嗨”。有句号会变成2句话。


有很多个连续符号,,,,,的情况下。。。所有的连续标点符号应该是同一句话。

这句话有很多个<span>感叹号</span>!!!!!跟着另外一句话有很多问号????


这是一大段话:在计算机科学和语言学中，语法分析（英：Syntactic analysis，也叫Parsing）是根据某种给定的形式文法对由单词序列（如英语单词序列）构成的输入文本进行分析并确定其语法结构的一种过程。语法分析器（Parser）通常是作为编译器或解释器的组件出现的，它的作用是进行语法检查、并构建由输入的单词组成的数据结构（一般是语法分析树、抽象语法树等层次化的数据结构）。语法分析器通常使用一个独立的词法分析器从输入字符流中分离出一个个的“单词”，并将单词流作为其输入。实际开发中，语法分析器可以手工编写，也可以使用工具（半）自动生成。

”以右引号起始的非正常句子。

最后一段话即使没有标点符号，也会自动作为一句话

DOC;

(new SentenceParser)
    ->load($content);

// Sinute\SentenceParser\ParseException: syntax error, unexpected '”' on line 14
// Because sentence start with ” is illegal

(new SentenceParser)
    ->ignoreError() // ignore illegal chars
    ->load($content);

/*****************************************
array(15) {
    [0]=> string(87) "小王说:“小李说:“小红说:“今天。天气不错啊。。。”。””。"
    [1]=> string(47) "话里套话的情况下, 应该算一句话。"
    [2]=> string(73) "小王说:“嗨”，引号后面没有句号会被当做同一句话。"
    [3]=> string(22) "小王说:“嗨”。"
    [4]=> string(28) "有句号会变成2句话。"
    [5]=> string(50) "有很多个连续符号,,,,,的情况下。。。"
    [6]=> string(51) "所有的连续标点符号应该是同一句话。"
    [7]=> string(48) "这句话有很多个<span>感叹号</span>!!!!!"
    [8]=> string(40) "跟着另外一句话有很多问号????"
    [9]=> string(269) "这是一大段话:在计算机科学和语言学中，语法分析（英：Syntactic analysis，也叫Parsing）是根据某种给定的形式文法对由单词序列（如英语单词序列）构成的输入文本进行分析并确定其语法结构的一种过程。"
    [10]=> string(246) "语法分析器（Parser）通常是作为编译器或解释器的组件出现的，它的作用是进行语法检查、并构建由输入的单词组成的数据结构（一般是语法分析树、抽象语法树等层次化的数据结构）。"
    [11]=> string(147) "语法分析器通常使用一个独立的词法分析器从输入字符流中分离出一个个的“单词”，并将单词流作为其输入。"
    [12]=> string(99) "实际开发中，语法分析器可以手工编写，也可以使用工具（半）自动生成。"
    [13]=> string(42) "”以右引号起始的非正常句子。"
    [14]=> string(69) "最后一段话即使没有标点符号，也会自动作为一句话"
}
/*****************************************/
```
