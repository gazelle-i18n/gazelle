#include <iostream>
#include <string>
#include <vector>
#include "spellcheck.h"
#include <climits>
#include <fstream>
#include <algorithm>
#include <list>
#include <bitset>

//TODO: Change int chars to bitset

const int max_word_len = 25;
const int max_edit_distance = 3;
const int max_char_distance = 3;
const int weight_ratio = 10;

int main() {
	
	std::cout << LONG_MAX << std::endl;
	long len = 1<<26;
	std::cout << len << std::endl;
	
	std::vector<std::list<word> > words;
	words.resize(max_word_len);
	
	std::ifstream in("/usr/share/dict/words");
	std::string str;
	int i = 0;
	size_t length;
	int num_words = 0;
	
	while(getline(in, str)) {
		++i;
		
		length = str.length();
		if(length>=max_word_len) { continue; }
		
		// Skip 's stuff
		if(length>2) {
			if(str.substr(length-2, 2) == "'s") {
				continue;
			}
		}
		
		std::transform(str.begin(), str.end(), str.begin(), ::tolower);
		
		
		long chars = make_binary_pattern(str, length);
		
		word w;
		w.str = str;
		w.weight = i%10; // note - placeholder
		w.chars = chars;
		
		words[length].push_back(w);
		
		num_words++;
	}
	
	std::cout << "loaded " << num_words << " words" << std::endl;
	
	//print_words(words);
	
	std::string input;
	
	std::cout << "Enter some comma-seperated words: ";
	while(getline(std::cin, input)) {
		if(input == "__die!__") {
			break;
		}
		// Break down into individual words
		std::list<std::string> search_words;
		size_t pos = -1;
		size_t last_pos = 0;
		
		do {
			pos++;
			pos = input.find(' ', pos);
			if(pos == last_pos) { continue; }
			search_words.push_back(input.substr(last_pos, pos-last_pos));
			last_pos = pos+1;
			
		} while (pos!=input.npos);
		
		// search_words now holds a list of words
		
		// Debugging - echo out words
		std::cout << "Got words: " << std::endl;
		for(std::list<std::string>::const_iterator i = search_words.begin(); i!=search_words.end(); i++) {
			std::cout << *i << std::endl;
		}
		
		std::cout << std::endl;
		
		// Spellcheck each individual word
		for(std::list<std::string>::const_iterator i = search_words.begin(); i!=search_words.end(); i++) {
			std::string search_word = *i;
			std::cout << "Checking word '" << search_word << "'" << std::endl;
			
			size_t length = search_word.length();
			long chars = make_binary_pattern(search_word, length);
			
			// Build up a list of candidates
			std::vector<std::list<candidate> > candidates(max_edit_distance+1);
			
			
			int start = 0;
			int end = 0;
			if(length>1) {
				start = length-1;
			}
			if(length+1<=max_word_len) {
				end = length+1;
			} else {
				end = max_word_len;
			}
			
			std::cout << "Binary: " << binary(chars) << std::endl;
			
			// search_len is the length of the words in the dictionary
			for(int search_len = start; search_len<=end; search_len++) {
				// test_word is the word we're testing against
				for(std::list<word>::iterator test_word = words[search_len].begin(); test_word!=words[search_len].end(); test_word++) {
					
					if(search_word == test_word->str) {
						std::cout << test_word->str << " : " << binary(test_word->chars) << " : " << binary(chars) <<  std::endl;
					}
					
					// Difference in the characters used
					std::bitset<26> diff(test_word->chars ^ chars);
					if(diff.count()<max_char_distance) {
						int distance = edit_distance(test_word->str, search_word);
						if(distance>max_edit_distance) {
							continue;
						}
						candidate cand;
						cand.str = test_word->str;
						cand.weight = test_word->weight;
						cand.distance = distance;
						candidates[distance].push_back(cand);
						//std::cout << "C " << cand.str << ' ' << diff.count() << ' ' << binary(test_word->chars) << ' '<< diff.to_string() << ' ' << cand.distance<< std::endl;
					}
					
					//std::cout << test_word->str << " : " << binary(test_word->chars) << " : " << test_word->weight <<  std::endl;
				}
			}
			// candidates is now a vector of linked lists of candidates
			std::cout << "got candidates" << std::endl;
			candidate best_candidate; // The best candidate we've found for the word
			bool found_candidate = false;
			
			// Start at an edit distance of 0 and go up to max_distance
			for(int distance = 0; distance<=max_edit_distance; distance++) {
				if(candidates[distance].size()>0) {
					candidates[distance].sort(compare_candidates); // Sort by weight
					if(!found_candidate) { // If we haven't found any candidates yet, fill in the highest weighted
						best_candidate = candidates[distance].front();
						found_candidate = true;
					} else if(candidates[distance].front().weight>best_candidate.weight*weight_ratio) {
						// If this one has a higher edit distance but is weighted weight_ratio times as large as the previous best
							best_candidate = candidates[distance].front();
						}
					}
				}
				
				// For debugging, print out the candidates
				for(std::list<candidate>::const_iterator z = candidates[distance].begin(); z!=candidates[distance].end(); z++) {
					
					std::cout << distance << ' ' << z->str << ' ' << z-> weight << std::endl;
				}
			}
			std::cout << "best candidiate: " << best_candidate.str << std::endl;
			
		}
		
		std::cout << "Enter some comma-seperated words: ";
	}
	
}

// Turn a string into a binary pattern representing its characters
// Each binary digit represents the existence of a single letter, starting with a = 1<<0 and ending with z = 1<<25
// Thus, centralized = 10000010100010100100011101
long make_binary_pattern(std::string str, size_t length) {
	char letter;
	long chars = 0;
	for(size_t c = 0; c<length; c++) {
		letter = str[c]-97;
		if(letter>25 || letter<0) {
			continue; 
		}
		chars = chars | (1<<letter);
	}
	return chars;
}

int edit_distance(std::string str1, std::string str2) {
	const size_t len1 = str1.length();
	const size_t len2 = str2.length();
	std::vector<std::vector<int> > map(len1+1, std::vector<int>(len2+1));
	
	for(int x = 0; x<=len1; x++) {
		map[x][0] = x; // inserting one character each column
	}
	for(int y = 0; y<=len2; y++) {
		map[0][y] = y; // deleting one character each row
	}	
	
	for(int x = 1; x<=len1; x++) {
		for(int y = 1; y<=len2; y++) {
			if(str1[x-1] == str2[y-1]) { // chars are the same, copy cost from previous cell
				map[x][y] = map[x-1][y-1];
			} else { // is it cheaper to delete, insert or substitute?
				map[x][y] = std::min(std::min(map[x-1][y], map[x][y-1]), map[x-1][y-1])+1;
				
				// Transposition
				if(str1[x-1] == str2[y-2] && str1[x-2] == str2[y-1]) {
					map[x][y] = std::min(map[x][y], map[x-2][y-2]+1);
				}
				
			}
		}
	}
	
	//print_table(map, len1, len2);
	
	return map[len1][len2];
}

int compare_candidates(candidate &c1, candidate &c2) {
	if(c1.weight<c2.weight) {
		return false;
	} else {
		return true;
	}
}

// Debug

void print_table(std::vector<std::vector<int> > table, int width, int height) {
	for(int x = 0; x<=width; x++) {
		for(int y = 0; y<=height; y++) {
			if(table[x][y]<10) {
				std::cout << ' ';
			}
			std::cout << table[x][y] << "  ";
		}
		std::cout << std::endl;
	}
}

// For debugging purposes only
void print_words(std::vector<std::list<word> > words) {
	for(std::vector<std::list<word> >::iterator len = words.begin(); len<words.end(); len++) {
		for(std::list<word>::iterator i = len->begin(); i!=len->end(); i++) {
			std::cout << i->str << " : " << binary(i->chars) << " : " << i->weight <<  std::endl;
		}
	}
}

// Also for debugging only
std::string binary(long num) {
	std::string str;
	for(int i = 25; i>=0; i--) {
		if(num & (1<<i)) {
			str.append("1");
		} else {
			str.append("0");
		}
	}
	return str;
}