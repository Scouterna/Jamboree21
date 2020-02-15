When(/^I click the talk button$/) do
  on(ArticlePage) do |page|
    page.wait_until_rl_module_ready('skins.minerva.scripts')
    page.talk_element.when_present.click
  end
end

When(/^no topic is present$/) do
  expect(on(ArticlePage).talk_overlay_content_header_element.when_present.text).to match 'There are no conversations about this page.'
end

When(/^I add a topic called "(.+)"$/) do |topic|
  step 'I click the add discussion button'
  on(ArticlePage) do |page|
    page.talk_overlay_summary = topic
    page.talk_overlay_body = 'Topic body is a really long text.'
    page.wait_until { page.talk_overlay_save_button_element.enabled? }
    page.talk_overlay_save_button
  end
end

When(/^I see the talk overlay$/) do
  on(ArticlePage).overlay_element.when_visible
end

When(/^I click the add discussion button$/) do
  on(ArticlePage).talkadd_element.when_present.click
end

Then(/^I should see the topic called "(.+)" in the list of topics$/) do |topic|
  # Timeout is high as the previous action hits the API which may take some time
  expect(on(ArticlePage).talk_overlay_first_topic_title_element.when_present(20).text).to match topic
end

Then(/^I should see the talk overlay$/) do
  on(ArticlePage) do |page|
    page.wait_until_rl_module_ready('mobile.talk.overlays')
    expect(on(ArticlePage).overlay_heading_element.when_present.text).to match 'Talk'
  end
end

Then(/^there should be no talk button$/) do
  expect(on(ArticlePage).talk_element).not_to be_visible
end

Then(/^there should be an add discussion button$/) do
  # give overlay time to fully load
  expect(on(ArticlePage).talkadd_element.when_present(10)).to be_visible
end

Then(/^there should be a save discussion button$/) do
  # give overlay time to fully load
  expect(on(ArticlePage).talktopic_save_element.when_present(10)).to be_visible
end
