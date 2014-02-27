<?php

/*
 * (c) Alexandre Quercia <alquerci@email.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Instinct\BC;

/**
 * @author Alexandre Quercia <alquerci@email.com>
 */
class Converter implements ConverterInterface
{
    private $namespace;

    /**
     * {@inheritDoc}
     */
    public function convert($content)
    {
        $this->namespace = null;

        // uses
        $content = $this->resolveUses($content);

        // current namespace
        $content = $this->resolveCurrentNamespace($content);

        // global namespace

        // backport constants
        $content = $this->backportConstants($content);

        return $content;
    }

    private function resolveUses($content)
    {
        $pattern = sprintf('/%s|%s|(?P<use_stmt>use (?P<class>[^\s;]+)(?:\s+as\s+(?P<alias>[^\s;]+))?\s*;)/',
            $this->getCommentRegexp(),
            $this->getQuotedRegexp()
        );

        preg_match_all($pattern, $content, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            if (!isset($match['use_stmt'])) {
                continue;
            }

            if (isset($match['alias'])) {
                $short = $match['alias'];
            } else {
                $parts = explode('\\', $match['class']);
                $short = array_pop($parts);
            }

            // remove use statement
            $pattern = sprintf('/%s|%s|(?P<use_stmt>%s)\n{1,2}/',
                $this->getCommentRegexp(),
                $this->getQuotedRegexp(),
                preg_quote($match['use_stmt'], '/')
            );
            $content = preg_replace_callback($pattern, function ($match) {
                if (!isset($match['use_stmt'])) {
                    return $match[0];
                }
            }, $content);

            $class = '\\'.strtr($match['class'], '\\', '_');

            $prefixRegexp = '(?:namespace|use|function)\s+|[^\x5C\$:>]';
            $suffixRegexp = sprintf('(?:\x5C%s)*', $this->getClassPattern());

            // resolve linked class name
            $pattern = sprintf('/%s|%s|(?:(?P<prefix>%s)\b(?P<short>%s)\b(?P<suffix>%s)?)/',
                $this->getCommentRegexp(),
                $this->getQuotedRegexp(),
                $prefixRegexp,
                preg_quote($short, '/'),
                $suffixRegexp
            );
            $content = preg_replace_callback($pattern, function ($match) use ($class) {
                if (!isset($match['short'])) {
                    return $match[0];
                }

                if (in_array(trim($match['prefix']), array('namespace', 'use', 'function'))) {
                    return $match[0];
                }

                return $match['prefix'].$class.strtr($match['suffix'], '\\', '_');
            }, $content);

            $pattern = sprintf('/(?P<doc_comment>%s)/',
                $this->getDocCommentRegexp()
            );
            $content = preg_replace_callback($pattern, function ($match) use ($class, $short, $prefixRegexp, $suffixRegexp) {
                if (!isset($match['doc_comment'])) {
                    return $match[0];
                }

                $docComment = $match['doc_comment'];

                $pattern = sprintf('/(?P<prefix>%s)\b(?P<short>%s)\b(?P<suffix>%s)?/',
                    $prefixRegexp,
                    preg_quote($short, '/'),
                    $suffixRegexp
                );
                $docComment = preg_replace_callback($pattern, function ($match) use ($class) {
                    if (!isset($match['short'])) {
                        return $match[0];
                    }

                    if (in_array(trim($match['prefix']), array('namespace', 'use', 'function'))) {
                        return $match[0];
                    }

                    return $match['prefix'].$class.strtr($match['suffix'], '\\', '_');
                }, $docComment);

                return $docComment;
            }, $content);
        }

        return $content;
    }

    private function resolveCurrentNamespace($content)
    {
        $pattern = sprintf('/%s|%s|(?P<namespace_stmt>namespace\s+(?P<namespace>[^\s;]+)\s*;)/',
            $this->getCommentRegexp(),
            $this->getQuotedRegexp()
        );

        preg_match_all($pattern, $content, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            if (!isset($match['namespace_stmt'])) {
                continue;
            }

            $namespace = strtr($match['namespace'], '\\', '_');

            // remove namespace statement
            $pattern = sprintf('/%s|%s|(?P<namespace_stmt>%s)\n{1,2}/',
                $this->getCommentRegexp(),
                $this->getQuotedRegexp(),
                preg_quote($match['namespace_stmt'], '/')
            );
            $content = preg_replace_callback($pattern, function ($match) {
                if (!isset($match['namespace_stmt'])) {
                    return $match[0];
                }
            }, $content);

            // resolve class name declaration
            $pattern = sprintf('/%s|%s|(?P<class_stmt>(?P<prefix>\s(?:class|interface)\s+)(?P<short>[^\s;]+))/',
                $this->getCommentRegexp(),
                $this->getQuotedRegexp()
            );
            $classes = array();
            $content = preg_replace_callback($pattern, function ($match) use ($namespace, &$classes) {
                if (isset($match['class_stmt'])) {
                    $classes[] = $match['short'];

                    return $match['prefix'].$namespace.'_'.$match['short'];
                }

                return $match[0];
            }, $content);

            // resolve class name
            foreach ($classes as $short) {
                $class = '\\'.$namespace.'_'.$short;

                $prefixRegexp = '(?:namespace|use|function)\s+|[^\x5C\$:>]';

                $pattern = sprintf('/%s|%s|(?:(?P<prefix>%s)\b(?P<short>%s)\b)/',
                    $this->getCommentRegexp(),
                    $this->getQuotedRegexp(),
                    $prefixRegexp,
                    preg_quote($short, '/')
                );
                $content = preg_replace_callback($pattern, function ($match) use ($class) {
                    if (!isset($match['short'])) {
                        return $match[0];
                    }

                    if (in_array(trim($match['prefix']), array('namespace', 'use', 'function'))) {
                        return $match[0];
                    }

                    return $match['prefix'].$class;
                }, $content);

                $pattern = sprintf('/(?P<doc_comment>%s)/',
                    $this->getDocCommentRegexp()
                );
                $content = preg_replace_callback($pattern, function ($match) use ($class, $short, $prefixRegexp) {
                    if (!isset($match['doc_comment'])) {
                        return $match[0];
                    }

                    $docComment = $match['doc_comment'];

                    $pattern = sprintf('/(?P<prefix>%s)\b(?P<short>%s)\b/',
                        $prefixRegexp,
                        preg_quote($short, '/')
                    );
                    $docComment = preg_replace_callback($pattern, function ($match) use ($class) {
                        if (!isset($match['short'])) {
                            return $match[0];
                        }

                        if (in_array(trim($match['prefix']), array('namespace', 'use', 'function'))) {
                            return $match[0];
                        }

                        return $match['prefix'].$class;
                    }, $docComment);

                    return $docComment;
                }, $content);
            }

            $this->namespace = $namespace;

            break;
        }

        return $content;
    }

    private function backportConstants($content)
    {
        $constantMap = array(
            '__DIR__' => 'dirname(__FILE__)',
            '__NAMESPACE__' => var_export($this->namespace, true),
        );

        foreach ($constantMap as $current => $backport) {
            $pattern = sprintf('/%s|%s|(?:(?P<prefix>[^\x5C\$:>])\b(?P<current>%s)\b)/',
                $this->getCommentRegexp(),
                $this->getQuotedRegexp(),
                preg_quote($current, '/')
            );

            $content = preg_replace_callback($pattern, function ($match) use ($backport) {
                if (isset($match['current'])) {
                    return $match['prefix'].$backport;
                }

                return $match[0];
            }, $content);
        }

        return $content;
    }

    private function getClassPattern()
    {
        $keywords = array(
            '__halt_compiler',
            'abstract',
            'and',
            'array',
            'as',
            'break',
            'callable',
            'case',
            'catch',
            'class',
            'clone',
            'const',
            'continue',
            'declare',
            'default',
            'die',
            'do',
            'echo',
            'else',
            'elseif',
            'empty',
            'enddeclare',
            'endfor',
            'endforeach',
            'endif',
            'endswitch',
            'endwhile',
            'eval',
            'exit',
            'extends',
            'final',
            'for',
            'foreach',
            'function',
            'global',
            'goto',
            'if',
            'implements',
            'include',
            'include_once',
            'instanceof',
            'insteadof',
            'interface',
            'isset',
            'list',
            'namespace',
            'new',
            'or',
            'print',
            'private',
            'protected',
            'public',
            'require',
            'require_once',
            'return',
            'static',
            'switch',
            'throw',
            'trait',
            'try',
            'unset',
            'use',
            'var',
            'while',
            'xor',
            '__CLASS__',
            '__DIR__',
            '__FILE__',
            '__FUNCTION__',
            '__LINE__',
            '__METHOD__',
            '__NAMESPACE__',
            '__TRAIT__',
        );

        $excludePattern = implode('|', $keywords);
        $namePattern = '[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*';

        $pattern = sprintf('(?!(?i:%s)|(?!%s))', $excludePattern, $namePattern);

        return $pattern;
    }

    private function getQuotedRegexp()
    {
        $regex = <<<'EOF'
(?x:
    (?: # heredoc and nowdoc
        <<<(?P<nowdoc_quote>'?) (?P<nowdoc_delimiter>[_[:alpha:]][_[:alnum:]]*) (?P=nowdoc_quote)
            \C*(?!(?P=nowdoc_end))
        (?P<nowdoc_end>\n(?P=nowdoc_delimiter))
    )
    |(?: # single quoted string
        '
            [^\\']*+
            (?:\\.[^\\']*+)*+
        '
    )
    |(?: # double quoted string
        "
            [^\\"]*+
            (?:\\.[^\\"]*+)*+
        "
    )
)
EOF;

        return $regex;
    }

    private function getCommentRegexp()
    {
        $regex = <<<'EOF'
(?x:
    (?s: # bloc
        \/\*
        .*?
        \*\/
    )
    |(?: # inline
        (?:\/\/|\#)
        [^\n]*
    )
)
EOF;

        return $regex;
    }

    private function getDocCommentRegexp()
    {
        $regex = <<<'EOF'
(?xs: # bloc
    \/\*\*
    .*?
    \*\/
)
EOF;

        return $regex;
    }
}
