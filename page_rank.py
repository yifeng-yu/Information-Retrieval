import networkx as nx

#G = nx.read_edgelist("/Users/yifeng/Desktop/hw4/data/edges.txt", create_using=nx.DiGraph())
#pagerank = nx.pagerank(G, alpha=0.85, personalization=None, max_iter=30, tol=1e-06, nstart=None, weight='weight',
                       #dangling=None)

graph = nx.read_edgelist("/Users/yifeng/Desktop/hw4/data/edges.txt")
page_rank = nx.pagerank(graph)
with open("external_pageRankFile.txt", "w") as f:
    for key, value in page_rank.items():
        f.write(f"/Users/yifeng/Desktop/hw4/data/latimes{key}={value}\n")