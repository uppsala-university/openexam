// JavaScript Document specific to View question
// @Author Ahsan Shahzad [MedfarmDoIT]

	/*----------------------------------------------------------*/ 
	/*	Sync answers in database every 30 seconds	    */
	/*----------------------------------------------------------*/
	testCounter = 0;
	// autosave answers every 30 seconds if something has got changed
	setInterval(function() {
		
		//insert text
		console.log(totalSyncs);
		testCounter++;
		if(testCounter > 3) {
			if($('.btn-next').length) {
				insertHTML("<h1>Finished this test and redirected<h1>");
				$('.btn-next').trigger('click');
			} else {
				console.log("finished test");
			}
		} else {
			insertHTML();
		}

	}, 2500);
	
	function insertHTML(html) {
		html = typeof(html) !== 'undefined'?html:multilineTxt;
		
		// Get the editor instance that we want to interact with.
      		jQuery.each(CKEDITOR.instances, function(){
         		//console.log(this.name);
			this.insertHtml( html );
         	});

	}

	var multilineTxt = 
		'<p>&quot;Cinderella&quot; by the Brothers Grimm</p>\
		\
		<p>Once upon a time, there lived a gentleman, who after his beautiful and kind wife died, married the proudest and meanest woman in all the land.</p>\
		\
		<p>She had two daughters from a previous marriage who were just as nasty and haughty as their mother.</p>\
		\
		<p>The gentleman also had a young daughter by another wife named Cinderella, who was filled with goodness and was one of the sweetest girls the kingdom had ever seen.</p>\
		\
		<p>Cinderella&#39;s stepmother was extremely jealous of her beauty and charm and made her do the hardest and most dreadful work in the house.</p>\
		\
		<p>Cinderella did the dishes, scrubbed the floor and made the bed all while her step-sisters rested on fancy beds had fun playing dress-up.</p>\
		\
		<p>Now it so happened that the King&#39;s son decided to give a ball, inviting all the young ladies in the land to attend.</p>\
		\
		<p>Cinderella&#39;s step-mother and step-sisters were delighted, and would talk of nothing but the ball all day long. They sent for the greatest designers in the kingdom to ensure that they looked their best.</p>\
		\
		<p>Cinderella offered to help them get ready for the ball for she had excellent taste and despite how her step-sisters treated her, she always gave them the best advice.</p>\
		\
		<p>As she helped them, the eldest sister asked, &quot;Cinderella, are you not going to the ball?&quot;<br />\
		Cinderella sadly lowered her head and said, &quot;No, you&#39;re only teasing me because I have nothing to wear and wouldn&#39;t fit in. Perhaps I could borrow something?&quot;<br />\
		&quot;Lend our clothes to such a dirty Cinderwench? We&#39;re not fools!&quot; they exclaimed.</p>\
		\
		<p>The sisters laughed cruelly and said, &quot;You would make everyone laugh at the sight of you, you Cinderwench!&quot;</p>\
		\
		<p>When the big day finally came, Cinderella accompanied her step-mother and step-sisters to the Court, and couldn&#39;t help but burst into tears as she watched them enter the beautiful ball.</p>\
		\
		<p>As she wept, Cinderella&#39;s fairy godmother appeared.</p>\
		\
		<p>&quot;Cinderella, why are you crying?&quot; she asked. &quot;You wish to attend the ball, is that not so?&quot;</p>\
		\
		<p>&quot;Y&acirc;&euro;&rdquo;es,&quot; cried Cinderella, between sobs.</p>\
		\
		<p>The fairy godmother smiled and said, &quot;Well, run into the garden and bring me a pumpkin.&quot;</p>\
		\
		<p>Cinderella immediately went to get the finest pumpkin she could find.</p>\
		\
		<p>When she brought it, her godmother struck the pumpkin with her wand, instantly turning it into a fine coach, plated with gold and silver.</p>\
		\
		<p>Next, she had Cinderella find some mice, and when she brought the furry little creatures back the fairy godmother tapped them each with her wand, turning them into six fine horses and a coachman.</p>\
		\
		<p>&quot;Well what do you say? Do you still think you are not able to attend the ball?&quot; asked her godmother.</p>\
		\
		<p>&quot;Oh yes!&quot; cried Cinderella, &quot;but should I go looking like this, in these rags?&quot;</p>\
		\
		<p>Her godmother only touched her with her wand and instantly Cinderella&#39;s rags turned into a dress of white and silver, sparkling with jewels.</p>\
		\
		<p>To top it off, fairy godmother gave Cinderella a pair of glass slippers, the prettiest in the whole world.</p>\
		\
		<p>&quot;The spell only lasts until midnight, so promise you will leave the ball before then,&quot; warned the Godmother.</p>\
		\
		<p>Cinderella promised to return before midnight, thanked her again and drove off to the ball.</p>\
		\
		<p>When Cinderella made her entrance, the dancing and music stopped as everyone turned to gaze at her beauty.</p>\
		\
		<p>No one recognized her, she was a complete mystery.</p>\
		\
		<p>The Prince rushed up to greet her, led her to the most honorable seat by his side and later took her out for a dance.</p>\
		\
		<p>Cinderella even made time to approach her step-sisters, who still did not recognize her, and shared some of the oranges the prince had presented to her as a gift.</p>\
		\
		<p>The Prince never left her side, and Cinderella was enjoying herself so much that she completely forgot the time!</p>\
		\
		<p>When the clock struck midnight, Cinderella was shocked and fled immediately, leaving one of her glass slippers behind in her haste.</p>\
		\
		<p>The Prince ran to follow her, but only managed to pick up the glass slipper she left behind.</p>\
		\
		<p>Cinderella managed to get home, but was quite out of breath and in her dirty old clothes.</p>\
		\
		<p>She was resting in bed when her two step-sisters stumbled into her room.</p>\
		\
		<p>&quot;You stayed really late!&quot; cried Cinderella, rubbing her eyes and stretching as if she had been sleeping.</p>\
		\
		<p>&quot;If you had been there you would have seen the most beautiful princess,&quot; exclaimed the eldest sister, &quot;she was so nice to us and had the undivided attention of the Prince.&quot;</p>\
		\
		<p>&quot;Her background is a mystery and the Prince would give anything to know who she was,&quot; said the youngest.</p>\
		\
		<p>A few days later the Prince declared that he would marry the woman whose foot fit in the slipper.</p>\
		\
		<p>His soldiers began to try the slipper on all the princesses and duchesses in the Court, but it was all in vain.</p>\
		\
		<p>Days later, it was brought to the two sisters who tried with all their might to make the slipper fit.</p>\
		\
		<p>Cinderella, who saw this, politely asked to try it.</p>\
		\
		<p>Her sisters burst out laughing at the idea, but the Prince ordered that everyone in the kingdom should have a try.</p>\
		\
		<p>When Cinderella&#39;s foot slid perfectly into the slipper, her sisters were astonished.</p>\
		\
		<p>Cinderella&#39;s fairy godmother appeared and with the flick of her wand turned Cinderella into the beautiful girl from the ball.</p>\
		\
		<p>The step-sisters dropped to their knees and begged for forgiveness for the awful way they treated her over the years.</p>\
		\
		<p>Cinderella lifted them up and embraced them, saying she forgave them with all her heart.</p>\
		\
		<p>Cinderella was then escorted to the Prince, dressed as beautiful as she was at the ball.</p>\
		\
		<p>A few days later they were married.</p>\
		\
		<p>Cinderella, who was no less good than beautiful, gave her two sisters rooms in the palace, and everyone lived happily ever after.</p>\
		\
		<p>And this story ended in very vague way. They could have made it better but they did not want to do that perhaps. The End!</p>\
		\
		###############################################################################################################################################################';
		
	
//console.log("ALHAMDULILAH");
		
