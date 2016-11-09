<?php
namespace Sinute\SentenceParser;

class SentenceParser
{
    const STATE_START    = 0; // 起始状态
    const STATE_END      = 1; // 结束状态
    const STATE_SENTENCE = 2; // 句状态
    const STATE_QUOTE    = 3; // 引用状态
    const STATE_ERROR    = 4; // 错误状态

    const SYMBOL_START       = 0; // 字符
    const SYMBOL_END         = 1; // 句尾
    const SYMBOL_QUOTE_START = 2; // 引用起始
    const SYMBOL_QUOTE_END   = 3; // 引用结束
    const SYMBOL_SPACE       = 4; // 空白

    protected $stateName = [
        self::STATE_START    => '起始状态',
        self::STATE_END      => '结束状态',
        self::STATE_SENTENCE => '句状态',
        self::STATE_QUOTE    => '引用状态',
        self::STATE_ERROR    => '错误状态',
    ];

    // 符号
    protected $symbolTable = [
        self::SYMBOL_END         => ['.', '!', '?', '。', '！', '？'], // 句尾
        self::SYMBOL_QUOTE_START => [/*"'", '"', */'“', '《', '【'], // 引用起始
        self::SYMBOL_QUOTE_END   => [/*"'", '"', */'”', '》', '】'], // 引用结束
        self::SYMBOL_SPACE       => ["\n", "\t", ' ', '　'], // 句首尾空白字符
        // self::SYMBOL_START 其他所有字符
    ];

    // 状态转移表
    protected $stateTransitionTable = [
        self::STATE_START    => [
            self::SYMBOL_START       => self::STATE_SENTENCE,
            self::SYMBOL_END         => self::STATE_END,
            self::SYMBOL_QUOTE_START => self::STATE_QUOTE,
            self::SYMBOL_QUOTE_END   => self::STATE_ERROR,
            self::SYMBOL_SPACE       => self::STATE_START,
        ],
        self::STATE_END      => [
            self::SYMBOL_START       => self::STATE_START,
            self::SYMBOL_END         => self::STATE_END,
            self::SYMBOL_QUOTE_START => self::STATE_START,
            self::SYMBOL_QUOTE_END   => self::STATE_ERROR,
            self::SYMBOL_SPACE       => self::STATE_START,
        ],
        self::STATE_SENTENCE => [
            self::SYMBOL_START       => self::STATE_SENTENCE,
            self::SYMBOL_END         => self::STATE_END,
            self::SYMBOL_QUOTE_START => self::STATE_QUOTE,
            self::SYMBOL_QUOTE_END   => self::STATE_ERROR,
            self::SYMBOL_SPACE       => self::STATE_SENTENCE,
        ],
        self::STATE_QUOTE    => [
            self::SYMBOL_START       => self::STATE_QUOTE,
            self::SYMBOL_END         => self::STATE_QUOTE,
            self::SYMBOL_QUOTE_START => self::STATE_QUOTE,
            self::SYMBOL_QUOTE_END   => self::STATE_SENTENCE, // or self::STATE_QUOTE
            self::SYMBOL_SPACE       => self::STATE_QUOTE,
        ],
    ];

    protected $quoteStack = [];

    protected $ignoreError = false;

    /**
     * 忽视错误状态
     *
     * @author Sinute
     * @date   2016-11-09
     * @param  boolean    $ignoreError 是否忽视错误, 默认在语法错误时抛出异常
     * @return \Sinute\SentenceParser\SentenceParser
     */
    public function ignoreError($ignoreError = true)
    {
        $this->ignoreError = $ignoreError;
        return $this;
    }

    /**
     * 获取字符符号
     *
     * @author Sinute
     * @date   2016-10-27
     * @param  string     $char 字符
     * @return 字符符号
     */
    protected function charSymbol($char)
    {
        foreach ($this->symbolTable as $symbol => $chars) {
            if (in_array($char, $chars)) {
                return $symbol;
            }
        }
        return self::SYMBOL_START;
    }

    /**
     * 状态转移
     *
     * @author Sinute
     * @date   2016-10-27
     * @param  integer     $state 状态
     * @param  string      $char  字符
     * @return integer            下一状态
     */
    protected function stateTransformation($state, $char)
    {
        $symbol   = $this->charSymbol($char);
        $newState = $this->stateTransitionTable[$state][$symbol];
        // special for quote
        if ($symbol == self::SYMBOL_QUOTE_START) {
            array_push($this->quoteStack, $char);
        } elseif ($symbol == self::SYMBOL_QUOTE_END) {
            array_pop($this->quoteStack);
        }
        if ($state == self::STATE_QUOTE && $symbol == self::SYMBOL_QUOTE_END) {
            if ($this->quoteStack) {
                $newState = self::STATE_QUOTE;
            } else {
                $newState = self::STATE_SENTENCE;
            }
        }
        // error recover
        if ($newState === self::STATE_ERROR && $this->ignoreError) {
            $newState = self::STATE_SENTENCE;
        }
        return $newState;
    }

    public function load($str)
    {
        $state                    = static::STATE_START;
        $chars                    = preg_split('/(?<!^)(?!$)/u', $str);
        $chars[count($chars) - 1] = trim($chars[count($chars) - 1]);
        $sentence                 = '';
        $sentences                = [];
        $lineNum                  = 1;
        foreach ($chars as $char) {
            if ($char == "\n") {
                $lineNum++;
            }
            $state = $this->stateTransformation($state, $char);
            if ($state === static::STATE_START) {
                // 保存并清空上一次结果
                if ($sentence) {
                    $sentences[] = new Sentence($sentence);
                    $sentence    = '';
                }
                // 跳过句首空格
                if (in_array($char, $this->symbolTable[static::SYMBOL_SPACE])) {
                    continue;
                }
                // 重新开始
                $sentence = $char;
            } elseif ($state === static::STATE_END) {
                $sentence .= $char;
            } elseif ($state === static::STATE_SENTENCE) {
                $sentence .= $char;
            } elseif ($state === static::STATE_QUOTE) {
                $sentence .= $char;
            } else {
                throw new ParseException("syntax error, unexpected '{$char}' on line {$lineNum} {$sentence}");
            }
        }
        // 补齐最后一段
        if ($sentence = trim($sentence, join($this->symbolTable[static::SYMBOL_SPACE]))) {
            $sentences[] = new Sentence($sentence);
        }
        return $sentences;
    }
}
