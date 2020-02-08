<?php

/**
 * @group MobileFrontend
 * @coversDefaultClass InlineDifferenceEngine
 */
class InlineDifferenceEngineTest extends MediaWikiTestCase {
	/**
	 * @covers ::generateTextDiffBody
	 * @covers ::generateContentDiffBody
	 */
	public function testInlineDiffs() {
		// Test that covers all possibilities, must match 004.phpt from wikidiff2
		$x = <<<END
foo bar
baz
quux
bang
END;
		$y = <<<END
foo test
baz
bang
END;
		$diffExpected = <<<END
<div class="mw-diff-inline-header"><!-- LINES 1,1 --></div>
<div class="mw-diff-inline-changed">foo <del>bar</del><ins>test</ins></div>
<div class="mw-diff-inline-context">baz</div>
<div class="mw-diff-inline-deleted"><del>quux</del></div>
<div class="mw-diff-inline-context">bang</div>

END;
		$diff = new InlineDifferenceEngine;
		$this->assertEquals(
			$this->strip( $diffExpected ),
			$diff->generateTextDiffBody( $this->strip( $x ), $this->strip( $y ) )
		);
	}

	private function strip( $text ) {
		// Windows, $@#!%#!
		return str_replace( "\r", '', $text );
	}
}
