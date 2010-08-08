#include <iostream>
#include <string>
#include <vector>
#include <list>

struct word {
	std::string str;
	int weight;
	long chars;
};

struct candidate {
	std::string str;
	int weight;
	int distance;
};

int main();

void print_words(std::vector<std::list<word> > words);

std::string binary(long num);
long make_binary_pattern(std::string str, size_t length);
int edit_distance(std::string str1, std::string str2);
void print_table(std::vector<std::vector<int> > table, int width, int height);
int compare_candidates(candidate &c1, candidate &c2);