import React, { useState, useEffect } from 'react';
import { StyleSheet, View, Text, FlatList, TouchableOpacity, ActivityIndicator, Alert, StatusBar, useColorScheme } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { Ionicons } from '@expo/vector-icons';

export default function StockistInventory() {
  const colorScheme = useColorScheme();
  const isDark = colorScheme === 'dark';
  const styles = getStyles(isDark);

  const [inventory, setInventory] = useState([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [stockistId, setStockistId] = useState(null);

  const API_URL = 'https://praanveda.net/web/api/mr.php';

  const loadInventory = async () => {
    try {
      const sId = await AsyncStorage.getItem('userId');
      setStockistId(sId);

      const response = await fetch(`${API_URL}?action=stockist_inventory&stockist_id=${sId}`);
      const result = await response.json();
      
      if (result.status === 'success') {
        setInventory(result.data || []);
      } else {
        Alert.alert('Error', result.message || 'Failed to load inventory');
      }
    } catch (e) {
      console.error(e);
      Alert.alert('Error', 'Network request failed');
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  };

  useEffect(() => {
    loadInventory();
  }, []);

  const handleRefresh = () => {
    setRefreshing(true);
    loadInventory();
  };

  const updateStock = async (productId, adjustment, currentQty) => {
    if (currentQty === 0 && adjustment < 0) return; // Can't go below zero

    // Optimistic UI update
    setInventory(prev => prev.map(item => {
      if (item.id === productId) {
        return { ...item, quantity: Math.max(0, parseInt(item.quantity) + adjustment) };
      }
      return item;
    }));

    try {
      const data = new FormData();
      data.append('stockist_id', stockistId);
      data.append('product_id', productId);
      data.append('adjustment', adjustment.toString());

      const res = await fetch(`${API_URL}?action=stockist_update_inventory`, {
        method: 'POST',
        headers: { 'Accept': 'application/json' },
        body: data
      });
      const result = await res.json();

      if (result.status !== 'success') {
        // Revert optimistic update on failure
        loadInventory();
        Alert.alert('Error', result.message || 'Failed to update stock');
      }
    } catch (e) {
      // Revert optimistic update on failure
      loadInventory();
      Alert.alert('Error', 'Network connection failed');
    }
  };

  const renderItem = ({ item }) => {
    const qty = parseInt(item.quantity);
    
    return (
      <View style={styles.card}>
        <View style={styles.cardContent}>
          <Text style={styles.productName}>{item.name}</Text>
          <Text style={styles.priceText}>₹{item.price}</Text>
        </View>
        <View style={styles.actionContainer}>
          <TouchableOpacity 
            style={[styles.btn, qty === 0 ? styles.btnDisabled : null]} 
            onPress={() => updateStock(item.id, -1, qty)}
            disabled={qty === 0}
          >
            <Ionicons name="remove" size={20} color={qty === 0 ? '#9CA3AF' : '#EF4444'} />
          </TouchableOpacity>
          <View style={styles.qtyBox}>
            <Text style={styles.qtyText}>{qty}</Text>
          </View>
          <TouchableOpacity 
            style={styles.btn} 
            onPress={() => updateStock(item.id, 1, qty)}
          >
            <Ionicons name="add" size={20} color="#10B981" />
          </TouchableOpacity>
        </View>
      </View>
    );
  };

  return (
    <View style={{ flex: 1, backgroundColor: isDark ? '#022C22' : '#064E3B' }}>
      <SafeAreaView style={{ flex: 1, backgroundColor: isDark ? '#111827' : '#F0FDF4' }} edges={['top']}>
        <StatusBar barStyle="light-content" backgroundColor={isDark ? '#022C22' : '#064E3B'} />
        
        <View style={styles.headerWrapper}>
          <Text style={styles.headerTitle}>My Inventory</Text>
          <Text style={styles.headerSubtitle}>Manage your available medicine stock</Text>
        </View>

        {loading ? (
        <ActivityIndicator size="large" color="#059669" style={{ marginTop: 40 }} />
      ) : (
        <FlatList
          data={inventory}
          keyExtractor={(item) => item.id.toString()}
          renderItem={renderItem}
          contentContainerStyle={styles.listContent}
          onRefresh={handleRefresh}
          refreshing={refreshing}
          ListEmptyComponent={
            <Text style={styles.emptyText}>No products found.</Text>
          }
        />
      )}
      </SafeAreaView>
    </View>
  );
}

const getStyles = (isDark: boolean) => StyleSheet.create({
  headerWrapper: {
    backgroundColor: isDark ? '#022C22' : '#064E3B',
    paddingHorizontal: 20,
    paddingBottom: 20,
    borderBottomLeftRadius: 24,
    borderBottomRightRadius: 24,
    elevation: 0,
    zIndex: 10,
    marginTop: -50,
    paddingTop: 50 + 16,
  },
  headerTitle: {
    fontSize: 22,
    fontWeight: '800',
    color: '#FFF',
    letterSpacing: -0.5,
  },
  headerSubtitle: {
    fontSize: 13,
    color: isDark ? '#34D399' : '#A7F3D0',
    fontWeight: '500',
    marginTop: 2,
  },
  listContent: {
    padding: 16,
    paddingBottom: 40,
  },
  card: {
    backgroundColor: isDark ? '#1F2937' : '#FFF',
    borderRadius: 16,
    padding: 16,
    marginBottom: 12,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: isDark ? 0.3 : 0.05,
    shadowRadius: 8,
    elevation: 2,
  },
  cardContent: {
    flex: 1,
    paddingRight: 10,
  },
  productName: {
    fontSize: 16,
    fontWeight: '700',
    color: isDark ? '#F9FAFB' : '#1F2937',
  },
  priceText: {
    fontSize: 13,
    color: isDark ? '#9CA3AF' : '#6B7280',
    marginTop: 4,
  },
  actionContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: isDark ? '#374151' : '#F9FAFB',
    borderRadius: 12,
    borderWidth: 1,
    borderColor: isDark ? '#4B5563' : '#E5E7EB',
  },
  btn: {
    padding: 10,
    alignItems: 'center',
    justifyContent: 'center',
  },
  btnDisabled: {
    opacity: 0.5,
  },
  qtyBox: {
    width: 40,
    alignItems: 'center',
    justifyContent: 'center',
  },
  qtyText: {
    fontSize: 16,
    fontWeight: '800',
    color: isDark ? '#F9FAFB' : '#111827',
  },
  emptyText: {
    textAlign: 'center',
    marginTop: 40,
    color: isDark ? '#6B7280' : '#9CA3AF',
    fontSize: 15,
  },
});
