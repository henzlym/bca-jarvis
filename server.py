from flask import Flask, jsonify, request
from newspaper import Article
import json
import feedparser
import re
from flask_cors import CORS
from urllib.parse import urlparse
# from transformers import GPT2TokenizerFast
# url = 'https://decrypt.co/120639/institutional-trader-survey-jp-morgan-crypto-artificial-intelligence'

# tokenizer = GPT2TokenizerFast.from_pretrained("gpt2")
# tokens = tokenizer(article.text)['input_ids']
app = Flask(__name__)
CORS(app)

def strip_tags(description):
    return re.sub('<[^<]+?>', '', description)
    
def get_domain_name(url):
    parsed_url = urlparse(url)
    hostname = parsed_url.hostname
    domain_name = hostname.split('.')[-2] + '.' + hostname.split('.')[-1]
    return domain_name
    
def is_valid_url(feed_url):
    url_pattern = re.compile(
        r'^(?:http|ftp)s?://' # http:// or https://
        r'(?:(?:[A-Z0-9](?:[A-Z0-9-]{0,61}[A-Z0-9])?\.)+(?:[A-Z]{2,6}\.?|[A-Z0-9-]{2,}\.?)|' #domain...
        r'localhost|' #localhost...
        r'\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})' # ...or ip
        r'(?::\d+)?' # optional port
        r'(?:/?|[/?]\S+)$', re.IGNORECASE
    )
    if feed_url == '':
        return False
    if not re.match(url_pattern, feed_url):
        return False
    return True
    
@app.route("/feed", methods = ['POST', 'GET'] )
def parse_feed():
    feed_url = ''
    
    # return response
    if request.method == 'POST':
        if 'url' not in request.form:
            return jsonify({'error': 'The url parameter is required in the request body'}), 400
        feed_url = request.form['url']
    else:
        if 'url' not in request.args:
            return jsonify({'error': 'The url parameter is required in the query string'}), 400
        feed_url = request.args.get('url')

    if not feed_url:
        return jsonify({'error': 'The url parameter cannot be empty'}), 400
    
    if is_valid_url(feed_url) == False:
        # Parse the feed
        return jsonify({'error': 'Invalid URL. Please enter a valid URL and try again.'}), 400
    
    parsed_feed = feedparser.parse(feed_url)

    if parsed_feed.status == 200:
        entries = parsed_feed.entries
        response = []
        for entry in entries:
            entry_data = {}
            if hasattr(entry, 'title'):
                entry_data['title'] = entry.title
            if hasattr(entry, 'link'):
                entry_data['link'] = entry.link
            if hasattr(entry, 'description'):
                entry_data['description'] = strip_tags(entry.description)
            if hasattr(entry, 'published'):
                entry_data['published'] = entry.published
            if hasattr(entry, 'author'):
                entry_data['author'] = entry.author

            category = entry.get("category", [])
            tags = entry.get("tags", [])
            entry_data['category'] = category
            entry_data['tags'] = tags
                
            response.append(entry_data)
        return jsonify(response)
    else:
        return jsonify({'error': 'Unable to parse the feed. Please check the URL and try again.'}), 400


@app.route("/crawl", methods = ['POST', 'GET'] )
def crawl():
    url = ''
    # return response
    if request.method == 'POST':
        if 'url' not in request.form:
            return jsonify({'error': 'The url parameter is required in the request body'}), 400
        url = request.form['url']
    else:
        if 'url' not in request.args:
            return jsonify({'error': 'The url parameter is required in the query string'}), 400
        url = request.args.get('url')

    if not url:
        return jsonify({'error': 'The url parameter cannot be empty'}), 400
    
    if is_valid_url(url) == False:
        # Parse the feed
        return jsonify({'error': 'Invalid URL. Please enter a valid URL and try again.'}), 400
      
    article = Article(url, keep_article_html=True)
    article.download()
    article.parse()
    article.nlp()
    
    meta_data = article.meta_data


    keywords = False
    
    if hasattr(article, 'keywords'):
        keywords = article.keywords
    
    domain_name = get_domain_name(url)
    
    news_article = {
        'domain':domain_name,
        'title':article.title,
        'authors':article.authors,
        'content':article.text,
        'html':article.article_html,
        'keywords':keywords,
        'summary':article.summary,
        'meta_title':meta_data.get('og:title'),
        'meta_description':meta_data.get('og:description')
    }
    return json.dumps(news_article)
    

if __name__ == "__main__":
    app.run(host='0.0.0.0',debug=True)